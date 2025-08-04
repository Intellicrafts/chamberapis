<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Lawyer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AvailabilitySlotController extends Controller
{
    /**
     * Display a listing of availability slots.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = AvailabilitySlot::with('lawyer:id,full_name');

            // Filter by lawyer
            if ($request->has('lawyer_id')) {
                $query->where('lawyer_id', $request->lawyer_id);
            }

            // Filter by availability
            if ($request->has('available')) {
                if ($request->boolean('available')) {
                    $query->available();
                } else {
                    $query->booked();
                }
            }

            // Filter by date
            if ($request->has('date')) {
                $query->forDate($request->date);
            }

            // Future slots only
            if ($request->boolean('future_only', true)) {
                $query->future();
            }

            // Sorting
            $query->orderBy('start_time');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $slots = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $slots,
                'message' => 'Availability slots retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving availability slots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created availability slot.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lawyer_id' => 'required|uuid|exists:lawyers,id',
                'start_time' => 'required|date|after:now',
                'end_time' => 'required|date|after:start_time',
            ]);

            // Check for overlapping slots
            $overlapping = AvailabilitySlot::where('lawyer_id', $validated['lawyer_id'])
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('start_time', '<=', $validated['start_time'])
                                ->where('end_time', '>=', $validated['end_time']);
                          });
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'This time slot overlaps with an existing slot'
                ], 422);
            }

            $slot = AvailabilitySlot::create($validated);
            $slot->load('lawyer:id,full_name');

            return response()->json([
                'success' => true,
                'data' => $slot,
                'message' => 'Availability slot created successfully'
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
                'message' => 'Error creating availability slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified availability slot.
     */
    public function show(AvailabilitySlot $availabilitySlot): JsonResponse
    {
        try {
            $availabilitySlot->load('lawyer:id,full_name');

            return response()->json([
                'success' => true,
                'data' => $availabilitySlot,
                'message' => 'Availability slot retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving availability slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified availability slot.
     */
    public function update(Request $request, AvailabilitySlot $availabilitySlot): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_time' => 'required|date|after:now',
                'end_time' => 'required|date|after:start_time',
                'is_booked' => 'boolean'
            ]);

            // Check for overlapping slots (excluding current slot)
            $overlapping = AvailabilitySlot::where('lawyer_id', $availabilitySlot->lawyer_id)
                ->where('id', '!=', $availabilitySlot->id)
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                          ->orWhere(function ($q) use ($validated) {
                              $q->where('start_time', '<=', $validated['start_time'])
                                ->where('end_time', '>=', $validated['end_time']);
                          });
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'This time slot overlaps with an existing slot'
                ], 422);
            }

            $availabilitySlot->update($validated);
            $availabilitySlot->load('lawyer:id,full_name');

            return response()->json([
                'success' => true,
                'data' => $availabilitySlot,
                'message' => 'Availability slot updated successfully'
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
                'message' => 'Error updating availability slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified availability slot.
     */
    public function destroy(AvailabilitySlot $availabilitySlot): JsonResponse
    {
        try {
            // Check if slot is booked
            if ($availabilitySlot->is_booked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a booked slot'
                ], 422);
            }

            $availabilitySlot->delete();

            return response()->json([
                'success' => true,
                'message' => 'Availability slot deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting availability slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Book an availability slot
     */
    public function book(AvailabilitySlot $availabilitySlot): JsonResponse
    {
        try {
            if ($availabilitySlot->is_booked) {
                return response()->json([
                    'success' => false,
                    'message' => 'This slot is already booked'
                ], 422);
            }

            if ($availabilitySlot->isPast()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot book a past slot'
                ], 422);
            }

            $availabilitySlot->markAsBooked();

            return response()->json([
                'success' => true,
                'data' => $availabilitySlot,
                'message' => 'Slot booked successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error booking slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel/unbook an availability slot
     */
    public function cancel(AvailabilitySlot $availabilitySlot): JsonResponse
    {
        try {
            if (!$availabilitySlot->is_booked) {
                return response()->json([
                    'success' => false,
                    'message' => 'This slot is not booked'
                ], 422);
            }

            $availabilitySlot->markAsAvailable();

            return response()->json([
                'success' => true,
                'data' => $availabilitySlot,
                'message' => 'Slot cancelled successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate weekly slots for a lawyer
     */
    public function generateWeekly(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lawyer_id' => 'required|uuid|exists:lawyers,id',
                'weeks' => 'required|integer|min:1|max:12',
                'schedule' => 'required|array',
                'schedule.*.day' => 'required|integer|min:0|max:6', // 0 = Sunday, 6 = Saturday
                'schedule.*.slots' => 'required|array|min:1',
                'schedule.*.slots.*.start_time' => 'required|date_format:H:i',
                'schedule.*.slots.*.end_time' => 'required|date_format:H:i|after:schedule.*.slots.*.start_time',
                'start_date' => 'nullable|date|after_or_equal:today'
            ]);

            $lawyer = Lawyer::findOrFail($validated['lawyer_id']);
            $startDate = $validated['start_date'] ? Carbon::parse($validated['start_date']) : Carbon::today();
            $createdSlots = [];

            // Generate slots for specified number of weeks
            for ($week = 0; $week < $validated['weeks']; $week++) {
                foreach ($validated['schedule'] as $daySchedule) {
                    $dayOfWeek = $daySchedule['day'];
                    
                    // Calculate the date for this day in the current week
                    $date = $startDate->copy()->addWeeks($week)->startOfWeek()->addDays($dayOfWeek);
                    
                    // Skip if date is in the past
                    if ($date->isPast()) {
                        continue;
                    }

                    foreach ($daySchedule['slots'] as $timeSlot) {
                        $startTime = $date->copy()->setTimeFromTimeString($timeSlot['start_time']);
                        $endTime = $date->copy()->setTimeFromTimeString($timeSlot['end_time']);

                        // Check for overlapping slots
                        $overlapping = AvailabilitySlot::where('lawyer_id', $validated['lawyer_id'])
                            ->where(function ($query) use ($startTime, $endTime) {
                                $query->whereBetween('start_time', [$startTime, $endTime])
                                      ->orWhereBetween('end_time', [$startTime, $endTime])
                                      ->orWhere(function ($q) use ($startTime, $endTime) {
                                          $q->where('start_time', '<=', $startTime)
                                            ->where('end_time', '>=', $endTime);
                                      });
                            })
                            ->exists();

                        if (!$overlapping) {
                            $slot = AvailabilitySlot::create([
                                'lawyer_id' => $validated['lawyer_id'],
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'is_booked' => false
                            ]);

                            $createdSlots[] = $slot;
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'created_slots_count' => count($createdSlots),
                    'slots' => $createdSlots
                ],
                'message' => 'Weekly slots generated successfully'
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
                'message' => 'Error generating weekly slots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete availability slots
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'slot_ids' => 'required|array',
                'slot_ids.*' => 'required|uuid|exists:availability_slots,id',
            ]);

            $slots = AvailabilitySlot::whereIn('id', $validated['slot_ids'])
                ->where('is_booked', false)
                ->get();

            $deletedCount = 0;
            foreach ($slots as $slot) {
                $slot->delete();
                $deletedCount++;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'deleted_count' => $deletedCount
                ],
                'message' => 'Slots deleted successfully'
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
                'message' => 'Error deleting slots: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available slots based on search criteria
     */
    public function getAvailable(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lawyer_id' => 'nullable|uuid|exists:lawyers,id',
                'date' => 'nullable|date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'min_duration' => 'nullable|integer|min:15',
            ]);

            $query = AvailabilitySlot::available()->future();

            // Filter by lawyer
            if (isset($validated['lawyer_id'])) {
                $query->where('lawyer_id', $validated['lawyer_id']);
            }

            // Filter by date
            if (isset($validated['date'])) {
                $query->forDate($validated['date']);
            } elseif (isset($validated['start_date']) && isset($validated['end_date'])) {
                $query->whereBetween('start_time', [$validated['start_date'], $validated['end_date']]);
            } elseif (isset($validated['start_date'])) {
                $query->where('start_time', '>=', $validated['start_date']);
            }

            // Filter by minimum duration
            if (isset($validated['min_duration'])) {
                $query->whereRaw('TIMESTAMPDIFF(MINUTE, start_time, end_time) >= ?', [$validated['min_duration']]);
            }

            // Sort by start time
            $query->orderBy('start_time');

            // Get results
            $slots = $query->with('lawyer:id,full_name')->get();

            return response()->json([
                'success' => true,
                'data' => $slots,
                'message' => 'Available slots retrieved successfully'
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
                'message' => 'Error retrieving available slots: ' . $e->getMessage()
            ], 500);
        }
    }
}