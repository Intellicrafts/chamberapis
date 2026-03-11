<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LawyerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class LawyerServiceController extends Controller
{
    /**
     * Get all services for the logged-in lawyer.
     */
    public function index(Request $request)
    {
        $services = LawyerService::where('lawyer_id', $request->user()->id)->get();
        return response()->json([
            'success' => true,
            'data' => $services
        ]);
    }

    /**
     * Bulk sync services for the logged-in lawyer.
     * Takes an array of services and replaces/updates the existing ones.
     */
    public function sync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'services' => 'required|array',
            'services.*.service_code' => 'required|string',
            'services.*.service_name' => 'required|string',
            'services.*.billing_model' => 'required|string|in:per_minute,flat,per_document',
            'services.*.rate' => 'required|numeric|min:0',
            'services.*.currency' => 'nullable|string',
            'services.*.icon' => 'nullable|string',
            'services.*.is_active' => 'nullable|boolean',
            'services.*.locked' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->user()->id;
        $servicesData = $request->input('services');
        $incomingCodes = collect($servicesData)->pluck('service_code')->toArray();

        DB::beginTransaction();
        try {
            // Delete services that are no longer in the payload
            LawyerService::where('lawyer_id', $userId)
                ->whereNotIn('service_code', $incomingCodes)
                ->delete();

            $updatedServices = [];
            // Update or Create the rest
            foreach ($servicesData as $serviceData) {
                // Determine `is_active` and `locked` with defaults
                $isActive = isset($serviceData['is_active']) ? filter_var($serviceData['is_active'], FILTER_VALIDATE_BOOLEAN) : true;
                $isLocked = isset($serviceData['locked']) ? filter_var($serviceData['locked'], FILTER_VALIDATE_BOOLEAN) : false;
                
                $service = LawyerService::updateOrCreate(
                    [
                        'lawyer_id' => $userId,
                        'service_code' => $serviceData['service_code'],
                    ],
                    [
                        'service_name' => $serviceData['service_name'],
                        'billing_model' => $serviceData['billing_model'],
                        'rate' => $serviceData['rate'],
                        'currency' => $serviceData['currency'] ?? 'INR',
                        'icon' => $serviceData['icon'] ?? null,
                        'is_active' => $isActive,
                        'locked' => $isLocked,
                    ]
                );
                $updatedServices[] = $service;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Services synchronized successfully',
                'data' => $updatedServices
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while syncing services: ' . $e->getMessage()
            ], 500);
        }
    }
}
