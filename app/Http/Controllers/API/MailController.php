<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Mail\AppMailService;
use Illuminate\Http\Request;

class MailController extends Controller
{
    public function send(Request $request, AppMailService $mailService)
    {
        $validated = $request->validate([
            'to' => 'required|array|min:1|max:10',
            'to.*' => 'required|email',
            'subject' => 'required|string|max:180',
            'heading' => 'nullable|string|max:180',
            'message' => 'required|string|min:3|max:5000',
            'action_text' => 'nullable|string|max:60',
            'action_url' => 'nullable|url|max:2048',
            'meta' => 'nullable|array',
        ]);

        $lines = preg_split('/\r\n|\r|\n/', trim($validated['message'])) ?: [];

        $mailService->send(
            to: $validated['to'],
            subject: $validated['subject'],
            view: 'emails.templates.generic-message',
            data: [
                'heading' => $validated['heading'] ?? $validated['subject'],
                'messageLines' => $lines,
                'actionText' => $validated['action_text'] ?? null,
                'actionUrl' => $validated['action_url'] ?? null,
                'meta' => $validated['meta'] ?? [],
            ]
        );

        return response()->json([
            'message' => 'Mail sent successfully',
            'success' => true,
        ]);
    }
}
