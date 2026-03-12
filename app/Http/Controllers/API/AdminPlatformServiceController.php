<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LawyerService;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminPlatformServiceController extends Controller
{
    /**
     * Get distinct platform services grouped by service_code.
     */
    public function index(Request $request)
    {
        try {
            // Group existing lawyer services to find unique platform-level services
            // We use the first matching record for display details
            $platformServices = LawyerService::select('service_code', 'service_name', 'billing_model', 'icon')
                ->groupBy('service_code', 'service_name', 'billing_model', 'icon')
                ->get();
                
            // Also fetch counts of how many lawyers have this service enabled
            $stats = LawyerService::select('service_code', DB::raw('count(*) as total_lawyers'), DB::raw('SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_lawyers'))
                ->groupBy('service_code')
                ->get()
                ->keyBy('service_code');
                
            $result = $platformServices->map(function($service) use ($stats) {
                $stat = $stats->get($service->service_code);
                $service->total_assigned = $stat ? $stat->total_lawyers : 0;
                $service->active_count = $stat ? $stat->active_lawyers : 0;
                return $service;
            });

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching platform services: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new platform service and auto-assign to all lawyers.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_code' => 'required|string|regex:/^[a-zA-Z0-9_]+$/',
            'service_name' => 'required|string|max:255',
            'billing_model' => 'required|string|in:per_minute,flat,per_document',
            'icon' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceCode = $request->input('service_code');

        // Check if service already exists somewhere
        $exists = LawyerService::where('service_code', $serviceCode)->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A service with this code already exists.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Get all lawyer users
            $lawyers = User::where('user_type', User::USER_TYPE_LAWYER)->get();
            $insertedCount = 0;

            foreach ($lawyers as $lawyer) {
                LawyerService::create([
                    'lawyer_id' => $lawyer->id,
                    'service_code' => $serviceCode,
                    'service_name' => $request->input('service_name'),
                    'billing_model' => $request->input('billing_model'),
                    'rate' => 0.00, // Default zero rate
                    'currency' => 'INR',
                    'icon' => $request->input('icon'),
                    'is_active' => false, // Default inactive so lawyer has to activate it
                    'locked' => false
                ]);
                $insertedCount++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Platform service created and assigned to ' . $insertedCount . ' lawyers.',
                'data' => [
                    'service_code' => $serviceCode,
                    'service_name' => $request->input('service_name'),
                    'billing_model' => $request->input('billing_model'),
                    'icon' => $request->input('icon'),
                    'total_assigned' => $insertedCount,
                    'active_count' => 0
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create platform service: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a platform service across all assigned lawyers.
     */
    public function update(Request $request, $serviceCode)
    {
        $validator = Validator::make($request->all(), [
            'service_name' => 'required|string|max:255',
            'billing_model' => 'required|string|in:per_minute,flat,per_document',
            'icon' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedRows = LawyerService::where('service_code', $serviceCode)
                ->update([
                    'service_name' => $request->input('service_name'),
                    'billing_model' => $request->input('billing_model'),
                    'icon' => $request->input('icon')
                ]);

            if ($updatedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found or no lawyers have this service.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Platform service updated for ' . $updatedRows . ' lawyers.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update platform service: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a platform service completely from all lawyers.
     */
    public function destroy($serviceCode)
    {
        try {
            $deletedRows = LawyerService::where('service_code', $serviceCode)->delete();

            if ($deletedRows === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found or already deleted.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Service removed completely from ' . $deletedRows . ' lawyer profiles.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete platform service: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the active status of a platform service across all lawyers.
     */
    public function toggleStatus(Request $request, $serviceCode)
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updatedRows = LawyerService::where('service_code', $serviceCode)
                ->update(['is_active' => $request->input('is_active')]);

            return response()->json([
                'success' => true,
                'message' => 'Service status updated globally for ' . $updatedRows . ' lawyers.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle platform service status: ' . $e->getMessage()
            ], 500);
        }
    }
}
