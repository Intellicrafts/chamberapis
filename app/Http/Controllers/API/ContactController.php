<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contact;
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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'full_name' => 'required|string|max:255',
            'email_address' => 'required|email',
            'phone_number' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'service_interested' => 'nullable|string|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|min:10',
        ]);

        // $validated['user_id'] = Auth::id();
        $validated['user_id'] = '1';

        $contact = Contact::create($validated);

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
