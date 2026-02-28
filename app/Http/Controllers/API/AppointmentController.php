<?php

namespace App\Http\Controllers\API;

use App\Events\AppointmentBooked;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Mail\AppMailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of appointments.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Appointment::with(['user:id,name', 'lawyer:id,full_name']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            } elseif ($request->has('date')) {
                $query->dateRange($request->date);
            }

            // Filter by future/past
            if ($request->boolean('future_only', false)) {
                $query->future();
            } elseif ($request->boolean('past_only', false)) {
                $query->past();
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'appointment_time');
            $sortDir = $request->get('sort_dir', 'asc');
            $query->orderBy($sortBy, $sortDir);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $appointments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'message' => 'Appointments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created appointment.
     */
    public function store(Request $request, AppMailService $mailService): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'lawyer_id' => 'required|integer|exists:lawyers,id',
                'appointment_time' => 'required|date|after:now',
                'duration_minutes' => 'required|integer|min:15|max:240',
                'status' => 'sometimes|string|in:' . implode(',', Appointment::getStatuses()),
                'meeting_link' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            // Default status to scheduled if not provided
            if (!isset($validated['status'])) {
                $validated['status'] = Appointment::STATUS_SCHEDULED;
            }

            $appointment = DB::transaction(function () use ($validated) {
                $appointment = Appointment::create($validated);
                // Avoid strict column projections here so booking does not fail on
                // environments where optional profile columns are not present yet.
                $appointment->load(['user', 'lawyer', 'lawyer.user']);

                return $appointment;
            });

            try {
                if ($appointment->user && $appointment->lawyer) {
                    event(new AppointmentBooked($appointment, $appointment->user, $appointment->lawyer));
                }
            } catch (\Throwable $eventException) {
                report($eventException);
            }

            try {
                $lawyerOfficialEmail = $appointment->lawyer?->email ?: $appointment->lawyer?->user?->email;

                $mailService->sendAppointmentBookedNotifications([
                    'id' => $appointment->id,
                    'appointment_time' => optional($appointment->appointment_time)->toDateTimeString(),
                    'duration_minutes' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'meeting_link' => $appointment->meeting_link,
                    'user_name' => $appointment->user?->name,
                    'user_email' => $appointment->user?->email,
                    'lawyer_name' => $appointment->lawyer?->full_name,
                    'lawyer_email' => $appointment->lawyer?->email,
                    'lawyer_official_email' => $lawyerOfficialEmail,
                ]);
            } catch (\Throwable $mailException) {
                report($mailException);
            }

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment created successfully'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified appointment.
     */
    public function show(Appointment $appointment): JsonResponse
    {
        try {
            $appointment->load(['user:id,name', 'lawyer:id,full_name']);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified appointment.
     */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'appointment_time' => 'sometimes|date|after:now',
                'duration_minutes' => 'sometimes|integer|min:15|max:240',
                'status' => 'sometimes|string|in:' . implode(',', Appointment::getStatuses()),
            ]);

            $appointment->update($validated);
            $appointment->load(['user:id,name', 'lawyer:id,full_name']);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified appointment.
     */
    public function destroy(Appointment $appointment): JsonResponse
    {
        try {
            $appointment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Appointment deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark an appointment as completed.
     */
    public function markAsCompleted(Appointment $appointment): JsonResponse
    {
        try {
            if (!$appointment->canBeCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This appointment cannot be marked as completed'
                ], 422);
            }

            $appointment->markAsCompleted();
            $appointment->load(['user:id,name', 'lawyer:id,full_name']);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment marked as completed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking appointment as completed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an appointment.
     */
    public function cancel(Appointment $appointment): JsonResponse
    {
        try {
            if (!$appointment->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This appointment cannot be cancelled'
                ], 422);
            }

            $appointment->markAsCancelled();
            $appointment->load(['user:id,name', 'lawyer:id,full_name']);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark an appointment as no-show.
     */
    public function markAsNoShow(Appointment $appointment): JsonResponse
    {
        try {
            if (!$appointment->canBeMarkedAsNoShow()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This appointment cannot be marked as no-show'
                ], 422);
            }

            $appointment->markAsNoShow();
            $appointment->load(['user:id,name', 'lawyer:id,full_name']);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment marked as no-show'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error marking appointment as no-show: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reschedule an appointment.
     */
    public function reschedule(Request $request, Appointment $appointment): JsonResponse
    {
        try {
            $validated = $request->validate([
                'appointment_time' => 'required|date|after:now',
                'duration_minutes' => 'sometimes|integer|min:15|max:240',
            ]);

            if (!$appointment->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This appointment cannot be rescheduled'
                ], 422);
            }

            $appointment->update($validated);
            $appointment->load(['user:id,name', 'lawyer:id,full_name']);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => 'Appointment rescheduled successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rescheduling appointment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appointments for a specific user.
     */
    public function getUserAppointments(string $userId): JsonResponse
    {
        try {
            $appointments = Appointment::forUser($userId)
                ->with(['user:id,name', 'lawyer:id,full_name'])
                ->orderBy('appointment_time')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'message' => 'User appointments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get appointments for a specific lawyer.
     */
    public function getLawyerAppointments(string $lawyerId): JsonResponse
    {
        try {
            $appointments = Appointment::forLawyer($lawyerId)
                ->with(['user:id,name', 'lawyer:id,full_name'])
                ->orderBy('appointment_time')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'message' => 'Lawyer appointments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving lawyer appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming appointments.
     */
    public function getUpcoming(Request $request): JsonResponse
    {
        try {
            $query = Appointment::scheduled()->future()
                ->with(['user:id,name', 'lawyer:id,full_name']);

            // Filter by user or lawyer if provided
            if ($request->has('user_id')) {
                $query->forUser($request->user_id);
            }

            if ($request->has('lawyer_id')) {
                $query->forLawyer($request->lawyer_id);
            }

            // Limit results if specified
            if ($request->has('limit')) {
                $query->limit($request->limit);
            }

            $appointments = $query->orderBy('appointment_time')->get();

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'message' => 'Upcoming appointments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving upcoming appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get today's appointments.
     */
    public function getTodaysAppointments(Request $request): JsonResponse
    {
        try {
            $query = Appointment::scheduled()->today()
                ->with(['user:id,name', 'lawyer:id,full_name']);

            // Filter by user or lawyer if provided
            if ($request->has('user_id')) {
                $query->forUser($request->user_id);
            }

            if ($request->has('lawyer_id')) {
                $query->forLawyer($request->lawyer_id);
            }

            $appointments = $query->orderBy('appointment_time')->get();

            return response()->json([
                'success' => true,
                'data' => $appointments,
                'message' => 'Today\'s appointments retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving today\'s appointments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a meeting link for an appointment.
     */
    public function generateMeetingLink(Appointment $appointment): JsonResponse
    {
        try {
            if (!$appointment->isScheduled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot generate meeting link for non-scheduled appointments'
                ], 422);
            }

            $meetingLink = $appointment->generateMeetingLink();
            $appointment->update(['meeting_link' => $meetingLink]);

            return response()->json([
                'success' => true,
                'data' => [
                    'meeting_link' => $meetingLink,
                    'appointment' => $appointment
                ],
                'message' => 'Meeting link generated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generating meeting link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk cancel appointments.
     */
    public function bulkCancel(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'appointment_ids' => 'required|array',
                'appointment_ids.*' => 'required|uuid|exists:appointments,id',
            ]);

            $appointments = Appointment::whereIn('id', $validated['appointment_ids'])
                ->where('status', Appointment::STATUS_SCHEDULED)
                ->where('appointment_time', '>', now())
                ->get();

            foreach ($appointments as $appointment) {
                $appointment->markAsCancelled();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'cancelled_count' => $appointments->count(),
                    'appointments' => $appointments
                ],
                'message' => 'Appointments cancelled successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling appointments: ' . $e->getMessage()
            ], 500);
        }
    }
}
