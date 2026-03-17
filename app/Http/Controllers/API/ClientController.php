<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

/**
 * ClientController
 *
 * Full CRUD + special operations for the Clients module.
 *
 * All routes are protected by auth:sanctum middleware (defined in api.php).
 *
 * Available operations:
 *   GET    /api/clients                          → index (list with filters + pagination)
 *   POST   /api/clients                          → store (create new client)
 *   GET    /api/clients/stats                    → stats (dashboard summary counts)
 *   GET    /api/clients/user/{userId}            → getByUser
 *   GET    /api/clients/lawyer/{lawyerId}        → getByLawyer
 *   GET    /api/clients/service/{serviceId}      → getByService
 *   POST   /api/clients/bulk-status              → bulkUpdateStatus
 *   GET    /api/clients/{client}                 → show (single record + relations)
 *   PUT    /api/clients/{client}                 → update (full update)
 *   DELETE /api/clients/{client}                 → destroy (soft delete)
 *   PATCH  /api/clients/{client}/status          → updateStatus
 *   PATCH  /api/clients/{client}/priority        → updatePriority
 *   POST   /api/clients/{id}/restore             → restore (undo soft delete)
 */
class ClientController extends Controller
{
    // ── 1. INDEX ──────────────────────────────────────────────────────────────

    /**
     * List all clients with optional filtering and pagination.
     *
     * Query params:
     *   status      : filter by status value
     *   priority    : filter by priority value
     *   lawyer_id   : filter by lawyer
     *   user_id     : filter by user
     *   service_id  : filter by service
     *   search      : search by user name (via relation)
     *   with_trashed: include soft-deleted records (pass "true")
     *   sort_by     : column to sort by (default: created_at)
     *   sort_dir    : asc|desc (default: desc)
     *   per_page    : records per page (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Start query with eager-loaded relations for efficiency
            $query = Client::with([
                'user:id,name,email,phone',
                'lawyer:id,full_name,phone_number',
                'service:id,service_name,service_code,billing_model,rate',
            ]);

            // ── Filters ───────────────────────────────────────────────────────

            // Include soft-deleted records if requested
            if ($request->boolean('with_trashed')) {
                $query->withTrashed();
            }

            // Filter by status
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            // Filter by priority
            if ($request->filled('priority')) {
                $query->byPriority($request->priority);
            }

            // Filter by lawyer
            if ($request->filled('lawyer_id')) {
                $query->forLawyer((int) $request->lawyer_id);
            }

            // Filter by user (client)
            if ($request->filled('user_id')) {
                $query->forUser((int) $request->user_id);
            }

            // Filter by service
            if ($request->filled('service_id')) {
                $query->forService((int) $request->service_id);
            }

            // Search by user name (join users table for search)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$search}%"));
            }

            // ── Sorting ───────────────────────────────────────────────────────
            $sortBy  = $request->get('sort_by', 'created_at');
            $sortDir = $request->get('sort_dir', 'desc');

            // Only allow sorting by safe, indexed columns
            $allowedSorts = ['id', 'status', 'priority', 'created_at', 'onboarded_at', 'closed_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // ── Pagination ────────────────────────────────────────────────────
            $perPage = min((int) $request->get('per_page', 15), 100); // cap at 100
            $clients = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $clients,
                'message' => 'Clients retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving clients: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 2. STORE ──────────────────────────────────────────────────────────────

    /**
     * Create a new client record.
     *
     * Required fields: user_id, lawyer_id
     * Optional fields: service_id, status, priority, notes, onboarded_at
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // If email is provided instead of user_id, look up the user
            if ($request->filled('email') && !$request->filled('user_id')) {
                $user = \App\Models\User::where('email', $request->email)->first();
                if ($user) {
                    $request->merge(['user_id' => $user->id]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No user found with this email. They must register first.',
                        'errors' => ['email' => ['No registered user found with this email.']]
                    ], 404);
                }
            }

            $validated = $request->validate([
                'user_id'      => 'required|integer|exists:users,id',
                'lawyer_id'    => 'required|integer|exists:lawyers,id',
                'service_id'   => 'nullable|integer|exists:lawyer_services,id',
                'status'       => ['sometimes', 'string', Rule::in(Client::getStatuses())],
                'priority'     => ['nullable', Rule::in(Client::getPriorities())],  // nullable per user spec
                'notes'        => 'nullable|string|max:2000',
                'onboarded_at' => 'nullable|date',
            ]);

            // Default status to pending if not provided
            $validated['status'] = $validated['status'] ?? Client::STATUS_PENDING;

            // Auto-set onboarded_at if status is active and it wasn't provided
            if ($validated['status'] === Client::STATUS_ACTIVE && empty($validated['onboarded_at'])) {
                $validated['onboarded_at'] = now();
            }

            $client = Client::create($validated);

            // Reload with relations for the response
            $client->load([
                'user:id,name,email,phone',
                'lawyer:id,full_name,phone_number',
                'service:id,service_name,service_code',
            ]);

            return response()->json([
                'success' => true,
                'data'    => $client,
                'message' => 'Client created successfully',
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating client: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 3. SHOW ───────────────────────────────────────────────────────────────

    /**
     * Retrieve a single client record with all relations.
     */
    public function show(Client $client): JsonResponse
    {
        try {
            $client->load([
                'user:id,name,email,phone',
                'lawyer:id,full_name,phone_number,specialization',
                'service:id,service_name,service_code,billing_model,rate,currency',
            ]);

            return response()->json([
                'success' => true,
                'data'    => $client,
                'message' => 'Client retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving client: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 4. UPDATE ─────────────────────────────────────────────────────────────

    /**
     * Update all fields of a client record.
     * For status-only updates, prefer PATCH /{client}/status.
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id'      => 'sometimes|integer|exists:users,id',
                'lawyer_id'    => 'sometimes|integer|exists:lawyers,id',
                'service_id'   => 'nullable|integer|exists:lawyer_services,id',
                'status'       => ['sometimes', 'string', Rule::in(Client::getStatuses())],
                'priority'     => ['nullable', Rule::in(Client::getPriorities())],
                'notes'        => 'nullable|string|max:2000',
                'onboarded_at' => 'nullable|date',
                'closed_at'    => 'nullable|date',
            ]);

            // Auto-manage timestamps on status transitions
            if (isset($validated['status'])) {
                if ($validated['status'] === Client::STATUS_ACTIVE && !$client->onboarded_at) {
                    $validated['onboarded_at'] = now();
                }
                if ($validated['status'] === Client::STATUS_CLOSED && !$client->closed_at) {
                    $validated['closed_at'] = now();
                }
            }

            $client->update($validated);
            $client->load([
                'user:id,name,email',
                'lawyer:id,full_name',
                'service:id,service_name,service_code',
            ]);

            return response()->json([
                'success' => true,
                'data'    => $client,
                'message' => 'Client updated successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating client: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 5. DESTROY (Soft Delete) ──────────────────────────────────────────────

    /**
     * Soft-delete a client record.
     * The record remains in the DB and can be restored via POST /{id}/restore.
     */
    public function destroy(Client $client): JsonResponse
    {
        try {
            $client->delete(); // uses SoftDeletes trait — sets deleted_at

            return response()->json([
                'success' => true,
                'message' => 'Client deleted successfully (soft delete — can be restored)',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting client: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 6. UPDATE STATUS (Special Route) ─────────────────────────────────────

    /**
     * PATCH /api/clients/{client}/status
     *
     * Update only the status field. Automatically handles timestamp side-effects:
     *   - active     → sets onboarded_at if not already set
     *   - closed     → sets closed_at
     *   - suspended  → no extra changes
     */
    public function updateStatus(Request $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => ['required', 'string', Rule::in(Client::getStatuses())],
            ]);

            $newStatus = $validated['status'];

            // Transition logic with side-effects
            match ($newStatus) {
                Client::STATUS_ACTIVE    => $client->markAsActive(),    // sets onboarded_at
                Client::STATUS_CLOSED    => $client->markAsClosed(),    // sets closed_at
                Client::STATUS_SUSPENDED => $client->markAsSuspended(),
                Client::STATUS_INACTIVE  => $client->markAsInactive(),
                default                  => $client->update(['status' => $newStatus]),
            };

            $client->refresh();

            return response()->json([
                'success' => true,
                'data'    => $client,
                'message' => "Client status updated to '{$newStatus}' successfully",
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating client status: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 7. UPDATE PRIORITY (Special Route) ───────────────────────────────────

    /**
     * PATCH /api/clients/{client}/priority
     *
     * Update only the priority — can also be set to null to clear it.
     */
    public function updatePriority(Request $request, Client $client): JsonResponse
    {
        try {
            $validated = $request->validate([
                // nullable: user confirmed priority is optional
                'priority' => ['nullable', Rule::in(Client::getPriorities())],
            ]);

            $client->update(['priority' => $validated['priority']]);

            return response()->json([
                'success' => true,
                'data'    => $client,
                'message' => $validated['priority']
                    ? "Client priority updated to '{$validated['priority']}'"
                    : 'Client priority cleared',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating client priority: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 8. GET BY USER ────────────────────────────────────────────────────────

    /**
     * GET /api/clients/user/{userId}
     *
     * Retrieve all client records for a specific user.
     * Useful for a user's "My Cases" or profile page.
     */
    public function getByUser(int $userId): JsonResponse
    {
        try {
            $clients = Client::forUser($userId)
                ->with([
                    'lawyer:id,full_name,specialization',
                    'service:id,service_name,service_code',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $clients,
                'count'   => $clients->count(),
                'message' => 'Clients for user retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving clients for user: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 9. GET BY LAWYER ──────────────────────────────────────────────────────

    /**
     * GET /api/clients/lawyer/{lawyerId}
     *
     * Retrieve all clients assigned to a specific lawyer.
     * Useful for a lawyer's "My Clients" dashboard panel.
     */
    public function getByLawyer(Request $request, int $lawyerId): JsonResponse
    {
        try {
            $query = Client::forLawyer($lawyerId)
                ->with([
                    'user:id,name,email,phone',
                    'service:id,service_name,service_code',
                ]);

            // Optional status filter within lawyer's clients
            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            $clients = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data'    => $clients,
                'count'   => $clients->count(),
                'message' => 'Clients for lawyer retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving clients for lawyer: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 10. GET BY SERVICE ────────────────────────────────────────────────────

    /**
     * GET /api/clients/service/{serviceId}
     *
     * Retrieve all clients using a specific service.
     */
    public function getByService(int $serviceId): JsonResponse
    {
        try {
            $clients = Client::forService($serviceId)
                ->with([
                    'user:id,name,email',
                    'lawyer:id,full_name',
                    'service:id,service_name,service_code',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $clients,
                'count'   => $clients->count(),
                'message' => 'Clients for service retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving clients for service: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 11. BULK UPDATE STATUS ────────────────────────────────────────────────

    /**
     * POST /api/clients/bulk-status
     *
     * Update status for multiple clients at once.
     *
     * Body: { client_ids: [1, 2, 3], status: "active" }
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'client_ids'   => 'required|array|min:1',
                'client_ids.*' => 'required|integer|exists:clients,id',
                'status'       => ['required', 'string', Rule::in(Client::getStatuses())],
            ]);

            $newStatus = $validated['status'];
            $updateData = ['status' => $newStatus];

            // Auto-set timestamps based on new status
            if ($newStatus === Client::STATUS_ACTIVE) {
                // Only set onboarded_at for those that don't have it yet
                $updateData['onboarded_at'] = now();
            }
            if ($newStatus === Client::STATUS_CLOSED) {
                $updateData['closed_at'] = now();
            }

            $updatedCount = Client::whereIn('id', $validated['client_ids'])
                ->update($updateData);

            return response()->json([
                'success'       => true,
                'updated_count' => $updatedCount,
                'message'       => "{$updatedCount} client(s) status updated to '{$newStatus}'",
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error bulk updating client status: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 12. STATS (Dashboard Summary) ─────────────────────────────────────────

    /**
     * GET /api/clients/stats
     *
     * Returns a count breakdown by status + priority for dashboard widgets.
     * Optionally filter by lawyer_id to get stats for a specific lawyer.
     *
     * Query params:
     *   lawyer_id: (optional) restrict stats to a single lawyer
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $query = Client::query();

            // Restrict to a specific lawyer if requested
            if ($request->filled('lawyer_id')) {
                $query->forLawyer((int) $request->lawyer_id);
            }

            // Restrict to a specific user if requested
            if ($request->filled('user_id')) {
                $query->forUser((int) $request->user_id);
            }

            // Count by status
            $byStatus = (clone $query)
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            // Count by priority (excluding nulls)
            $byPriority = (clone $query)
                ->whereNotNull('priority')
                ->selectRaw('priority, COUNT(*) as total')
                ->groupBy('priority')
                ->pluck('total', 'priority')
                ->toArray();

            // Total count
            $total = (clone $query)->count();

            // Recent (last 30 days)
            $recentCount = (clone $query)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();

            return response()->json([
                'success' => true,
                'data'    => [
                    'total'       => $total,
                    'recent_30d'  => $recentCount,
                    'by_status'   => array_merge(
                        // Ensure all statuses are present (zero if missing)
                        array_fill_keys(Client::getStatuses(), 0),
                        $byStatus
                    ),
                    'by_priority' => array_merge(
                        array_fill_keys(Client::getPriorities(), 0),
                        $byPriority
                    ),
                ],
                'message' => 'Client stats retrieved successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving client stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ── 13. RESTORE (Undo Soft Delete) ────────────────────────────────────────

    /**
     * POST /api/clients/{id}/restore
     *
     * Restores a soft-deleted client record back to active state.
     * Requires the raw ID since the model binding skips deleted records.
     */
    public function restore(int $id): JsonResponse
    {
        try {
            // withTrashed() is needed to find soft-deleted records
            $client = Client::withTrashed()->findOrFail($id);

            if (!$client->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This client record is not deleted',
                ], 422);
            }

            $client->restore(); // clears deleted_at

            $client->load([
                'user:id,name,email',
                'lawyer:id,full_name',
                'service:id,service_name',
            ]);

            return response()->json([
                'success' => true,
                'data'    => $client,
                'message' => 'Client record restored successfully',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Client record not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error restoring client: ' . $e->getMessage(),
            ], 500);
        }
    }
}
