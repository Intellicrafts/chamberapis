<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ConsultationSession;
use App\Models\ConsultationMessage;
use App\Events\UserJoinedConsultation;
use App\Events\ConsultationSessionEnded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConsultationSessionController extends Controller
{
    /**
     * Get all active sessions for authenticated user
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        $sessions = ConsultationSession::forUser($userId)
            ->with(['appointment', 'user', 'lawyer', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->latest()
            ->paginate(15);

        return response()->json($sessions);
    }

    /**
     * Initialize consultation session for an appointment
     * Called when join button is clicked
     */
    public function start(Request $request, $appointmentId)
    {
        try {
            $appointment = Appointment::with(['user', 'lawyer'])->findOrFail($appointmentId);
            $userId = auth()->id();
            $user = auth()->user();

            $isParticipant = false;
            if ($appointment->user_id == $userId) {
                $isParticipant = true;
            } elseif ($user && $user->lawyer && $user->lawyer->id == $appointment->lawyer_id) {
                $isParticipant = true;
            } elseif ($appointment->lawyer_id == $userId) {
                $isParticipant = true;
            }

            // Verify user is a participant
            if (!$isParticipant) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 403);
            }

            // Check if can join
            if (!$appointment->canJoinConsultation()) {
                return response()->json([
                    'error' => 'Consultation cannot be joined yet',
                    'can_join' => false,
                    'minutes_until_join' => $appointment->minutes_until_join
                ], 400);
            }

            // Check if session already exists
            $existingSession = $appointment->consultationSession;
            
            if ($existingSession) {
                // Check if session is still valid
                if ($existingSession->hasExpired()) {
                    $existingSession->update([
                        'status' => 'expired',
                        'end_reason' => 'timeout'
                    ]);

                    return response()->json([
                        'error' => 'Session has expired'
                    ], 410);
                }

                // Join existing session
                return $this->joinExistingSession($existingSession, $userId);
            }

            // Create new session
            $session = $this->createSession($appointment, $userId);

            return response()->json([
                'message' => 'Session created successfully',
                'session' => $session->load(['user', 'lawyer', 'appointment']),
                'session_token' => $session->session_token,
                'status' => $session->status
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error starting consultation session: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to start session',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new consultation session
     */
    protected function createSession(Appointment $appointment, int $userId)
    {
        $duration = $appointment->consultation_duration_minutes ?? 55;
        $scheduledStart = $appointment->appointment_time;
        $scheduledEnd = $scheduledStart->copy()->addMinutes($duration);

        $session = DB::transaction(function () use ($appointment, $userId, $scheduledStart, $scheduledEnd, $duration) {
            $session = ConsultationSession::create([
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id,
                'lawyer_id' => $appointment->lawyer_id,
                'status' => 'waiting',
                'scheduled_start_time' => $scheduledStart,
                'scheduled_end_time' => $scheduledEnd,
                'duration_minutes' => $duration,
            ]);

            // Mark appropriate participant as joined
            if ($userId == $appointment->user_id) {
                $session->markUserJoined();
                
                // Create system message
                ConsultationMessage::createSystemMessage(
                    $session->id,
                    'User joined the consultation'
                );
            } else {
                $session->markLawyerJoined();
                
                ConsultationMessage::createSystemMessage(
                    $session->id,
                    'Lawyer joined the consultation'
                );
            }

            // Update appointment status
            $appointment->update([
                'consultation_status' => 'in_progress',
                'consultation_join_time' => now(),
            ]);

            return $session;
        });

        return $session;
    }

    /**
     * Join an existing session
     */
    protected function joinExistingSession(ConsultationSession $session, int $userId)
    {
        $isClient = ($userId == $session->user_id);

        // Mark participant as joined
        if ($isClient && !$session->user_joined_at) {
            $session->markUserJoined();
            
            ConsultationMessage::createSystemMessage(
                $session->id,
                'User joined the consultation'
            );
        } elseif (!$isClient && !$session->lawyer_joined_at) {
            $session->markLawyerJoined();
            
            ConsultationMessage::createSystemMessage(
                $session->id,
                'Lawyer joined the consultation'
            );
        }

        // Broadcast join event
        try {
            $user = auth()->user();
            $userType = $isClient ? 'user' : 'lawyer';
            broadcast(new UserJoinedConsultation($session, $userId, $user->name, $userType))->toOthers();
        } catch (\Exception $e) {
            Log::info('Broadcasting not configured for join event: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Joined session successfully',
            'session' => $session->fresh()->load(['user', 'lawyer', 'appointment']),
            'session_token' => $session->session_token,
            'status' => $session->status
        ]);
    }

    /**
     * Get session status and details by token
     */
    public function show(Request $request, $sessionToken)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)
            ->with(['user', 'lawyer', 'appointment'])
            ->firstOrFail();

        // Verify user is a participant
        if (!$session->isParticipant(auth()->id())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if session has expired
        if ($session->hasExpired() && $session->status === 'active') {
            $session->update([
                'status' => 'expired',
                'actual_end_time' => $session->scheduled_end_time,
                'end_reason' => 'timeout'
            ]);
        }

        $userType = auth()->id() == $session->user_id ? 'user' : 'lawyer';
        $otherParticipant = $userType === 'user' ? $session->lawyer : $session->user;
        $hasOtherJoined = $userType === 'user' ? $session->lawyer_joined_at : $session->user_joined_at;

        return response()->json([
            'session' => $session,
            'user_type' => $userType,
            'other_participant' => $otherParticipant,
            'other_participant_joined' => !is_null($hasOtherJoined),
            'is_active' => $session->isActive(),
            'has_expired' => $session->hasExpired(),
            'time_remaining_minutes' => $session->scheduled_end_time->diffInMinutes(now(), false),
        ]);
    }

    /**
     * Check if user can join a specific session
     */
    public function canJoin(Request $request, $sessionToken)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)->firstOrFail();
        $userId = auth()->id();

        $canJoin = $session->isParticipant($userId) && $session->canBeJoined();

        return response()->json([
            'can_join' => $canJoin,
            'status' => $session->status,
            'has_expired' => $session->hasExpired(),
            'scheduled_start' => $session->scheduled_start_time,
            'scheduled_end' => $session->scheduled_end_time,
        ]);
    }

    /**
     * End the consultation session
     */
    public function end(Request $request, $sessionToken)
    {
        $session = ConsultationSession::where('session_token', $sessionToken)->firstOrFail();
        $userId = auth()->id();

        // Verify user is a participant
        if (!$session->isParticipant($userId)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reason' => 'sometimes|string|in:completed,cancelled',
        ]);

        $reason = $request->input('reason', 'completed');

        // End the session
        $session->endSession($userId, $reason);

        // Create system message
        $userName = $userId == $session->user_id ? 'User' : 'Lawyer';
        ConsultationMessage::createSystemMessage(
            $session->id,
            "{$userName} ended the consultation"
        );

        // Broadcast session ended event
        try {
            broadcast(new ConsultationSessionEnded($session, $reason))->toOthers();
        } catch (\Exception $e) {
            Log::info('Broadcasting not configured for end event: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Session ended successfully',
            'session' => $session->fresh()->load(['analytics']),
        ]);
    }

    /**
     * Get active session for current user (if any)
     */
    public function getActiveSession(Request $request)
    {
        $userId = auth()->id();

        $activeSession = ConsultationSession::forUser($userId)
            ->whereIn('status', ['waiting', 'active'])
            ->with(['user', 'lawyer', 'appointment'])
            ->latest()
            ->first();

        if (!$activeSession) {
            return response()->json([
                'has_active_session' => false
            ]);
        }

        // Check if expired
        if ($activeSession->hasExpired()) {
            $activeSession->update([
                'status' => 'expired',
                'end_reason' => 'timeout'
            ]);

            return response()->json([
                'has_active_session' => false
            ]);
        }

        $userType = $userId == $activeSession->user_id ? 'user' : 'lawyer';

        return response()->json([
            'has_active_session' => true,
            'session' => $activeSession,
            'session_token' => $activeSession->session_token,
            'user_type' => $userType,
        ]);
    }
}
