<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lawyer;
use App\Models\LawyerEnrollmentStatusLog;
use Illuminate\Support\Facades\Validator;

class LawyerEnrollmentController extends Controller
{
    /**
     * Update the enrollment status of a lawyer and log the change.
     */
    public function updateEnrollmentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'status' => 'required' 
            // The status can be 0 (pending), 1 (enrollment verified), 2 (admin verified)
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');
        $statusValue = $request->input('status');

        $lawyer = Lawyer::where('user_id', $userId)->first();

        if (!$lawyer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lawyer profile not found for this user.'
            ], 404);
        }

        // Update the status
        $lawyer->status = (string)$statusValue;
        
        // Also update is_verified based on status 2 (admin verified)
        if ((string)$statusValue === '2') {
            $lawyer->is_verified = true;
        } else {
            // Optional: reset is_verified if you want it to revert when status drops below 2
            // $lawyer->is_verified = false; 
        }

        $lawyer->save();

        // Create log entry
        LawyerEnrollmentStatusLog::create([
            'user_id' => $userId,
            'status' => (string)$statusValue
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Enrollment status updated successfully',
            'data' => [
                'lawyer_id' => $lawyer->id,
                'user_id' => $userId,
                'new_status' => $statusValue
            ]
        ]);
    }

    /**
     * Get the enrollment status and its history for a lawyer.
     */
    public function getEnrollmentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->input('user_id');

        $lawyer = Lawyer::where('user_id', $userId)->first();

        if (!$lawyer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lawyer profile not found for this user.'
            ], 404);
        }

        $logs = LawyerEnrollmentStatusLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'current_status' => $lawyer->status,
                'is_verified' => $lawyer->is_verified,
                'history' => $logs
            ]
        ]);
    }
}
