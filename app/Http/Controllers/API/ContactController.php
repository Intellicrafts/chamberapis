<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\User;
use App\Services\Mail\AppMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    // GET all contacts for the authenticated user
    public function index()
    {
        $contacts = Contact::where('user_id', Auth::id())->latest()->get();
        return response()->json($contacts);
    }

    // POST: Store new contact
    public function store(Request $request, AppMailService $mailService)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'name' => 'nullable|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'email_address' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'phone_number' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'service' => 'nullable|string|max:255',
            'service_interested' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10|max:5000',
        ]);

        $fullName = trim((string) ($validated['full_name'] ?? $validated['name'] ?? ''));
        $email = trim((string) ($validated['email_address'] ?? $validated['email'] ?? ''));

        if ($fullName === '') {
            return response()->json([
                'message' => 'The name field is required.',
                'errors' => ['name' => ['The name field is required.']],
            ], 422);
        }

        if ($email === '') {
            return response()->json([
                'message' => 'The email field is required.',
                'errors' => ['email' => ['The email field is required.']],
            ], 422);
        }

        $fallbackUserId = env('CONTACT_FALLBACK_USER_ID');
        $userId = Auth::id() ?? ($validated['user_id'] ?? null) ?? $fallbackUserId ?? User::query()->value('id');
        if (!$userId) {
            return response()->json([
                'message' => 'No user available to associate this contact record.',
            ], 422);
        }

        $contactPayload = [
            'user_id' => $userId,
            'full_name' => $fullName,
            'email_address' => $email,
            'phone_number' => $validated['phone_number'] ?? $validated['phone'] ?? null,
            'company' => $validated['company'] ?? null,
            'service_interested' => $validated['service_interested'] ?? $validated['service'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
        ];

        $contact = Contact::create($contactPayload);

        try {
            $mailService->sendContactNotifications($contact->toArray());
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json(['message' => 'Contact submitted successfully', 'contact' => $contact], 201);
    }

    // GET: Show single contact
    public function show(Contact $contact)
    {
        $this->authorize('view', $contact);

        return response()->json($contact);
    }

    // PUT: Update contact
    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email_address' => 'sometimes|email',
            'phone_number' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'service_interested' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'sometimes|string|min:10',
            'status' => 'sometimes|string|in:pending,resolved,new',
        ]);

        $contact->update($validated);

        return response()->json(['message' => 'Contact updated successfully', 'contact' => $contact]);
    }

    // DELETE: Remove contact
    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return response()->json(['message' => 'Contact deleted successfully']);
    }
}
