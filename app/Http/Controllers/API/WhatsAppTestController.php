<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppService;
use App\Models\WhatsAppLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppTestController — Test & debug WhatsApp integration
 *
 * Routes:
 *   GET  /api/whatsapp/status    → health check (config, Twilio reachable?)
 *   POST /api/whatsapp/test-send → send a test message to a given number
 *   GET  /api/whatsapp/logs      → recent log entries
 */
class WhatsAppTestController extends Controller
{
    /**
     * Health check — verifies config + Twilio credentials are valid.
     */
    public function status(): \Illuminate\Http\JsonResponse
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from  = config('services.twilio.whatsapp_from');

        $checks = [
            'twilio_sid_set'            => !empty($sid),
            'twilio_token_set'          => !empty($token),
            'twilio_whatsapp_from_set'  => !empty($from),
            'twilio_whatsapp_from_value'=> $from,
            'queue_connection'          => config('queue.default'),
            'broadcast_connection'      => config('broadcasting.default'),
            'app_env'                   => config('app.env'),
            'log_level'                 => config('logging.channels.' . config('logging.default') . '.level', config('logging.channels.single.level', 'debug')),
        ];

        // Check if whatsapp_logs table exists
        try {
            $logCount = WhatsAppLog::count();
            $checks['whatsapp_logs_table'] = true;
            $checks['total_log_entries'] = $logCount;
        } catch (\Throwable $e) {
            $checks['whatsapp_logs_table'] = false;
            $checks['whatsapp_logs_error'] = $e->getMessage();
        }

        // Quick Twilio connectivity test
        if (!empty($sid) && !empty($token)) {
            try {
                $client = new \Twilio\Rest\Client($sid, $token);
                $account = $client->api->v2010->accounts($sid)->fetch();
                $checks['twilio_connection'] = 'OK';
                $checks['twilio_account_status'] = $account->status;
            } catch (\Throwable $e) {
                $checks['twilio_connection'] = 'FAILED';
                $checks['twilio_connection_error'] = $e->getMessage();
            }
        } else {
            $checks['twilio_connection'] = 'SKIPPED — credentials missing';
        }

        $allOk = !empty($sid) && !empty($token) && !empty($from)
                 && ($checks['twilio_connection'] === 'OK');

        return response()->json([
            'success'  => $allOk,
            'status'   => $allOk ? 'WhatsApp system is ready' : 'Issues detected — check details',
            'checks'   => $checks,
            'timestamp'=> now()->toDateTimeString(),
        ]);
    }

    /**
     * Send a test WhatsApp message.
     *
     * POST /api/whatsapp/test-send
     * Body: { "phone": "+919557824745", "message": "Hello test!" }
     */
    public function testSend(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'phone'   => 'required|string|min:10',
            'message' => 'sometimes|string|max:1600',
        ]);

        $phone   = $request->input('phone');
        $message = $request->input('message',
            "🏛️ *MeraVakil*\n━━━━━━━━━━━━━━━━━━━━━━\n\n"
          . "✅ *WhatsApp Test Successful!*\n\n"
          . "This is a test message from MeraVakil Chambers API.\n\n"
          . "📅 Time: " . now()->format('d M Y, h:i A') . " IST\n"
          . "🌐 Env: " . config('app.env') . "\n\n"
          . "━━━━━━━━━━━━━━━━━━━━━━\n"
          . "📲 *dev.merabakil.com*  |  ☎️ Support: +91-9557824745"
        );

        Log::info('WhatsApp test send requested.', ['phone' => $phone]);

        try {
            $service = new WhatsAppService();
            $sid = $service->send($phone, $message, 'test_manual', null, true);

            return response()->json([
                'success'     => true,
                'message'     => 'Test WhatsApp message sent successfully!',
                'twilio_sid'  => $sid,
                'sent_to'     => $phone,
                'timestamp'   => now()->toDateTimeString(),
            ]);

        } catch (\Throwable $e) {
            Log::error('WhatsApp test send failed.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'code'  => $e->getCode(),
            ]);

            $hint = '';
            if (str_contains($e->getMessage(), '63007') || str_contains($e->getMessage(), 'sandbox')) {
                $hint = 'Twilio Sandbox: The recipient must first send "join <keyword>" to +14155238886 on WhatsApp.';
            } elseif (str_contains($e->getMessage(), '21408')) {
                $hint = 'Permission denied — check your Twilio account permissions for WhatsApp.';
            } elseif (str_contains($e->getMessage(), '21211') || str_contains($e->getMessage(), '21614')) {
                $hint = 'Invalid phone number format or the number is not WhatsApp-capable.';
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test WhatsApp message.',
                'error'   => $e->getMessage(),
                'code'    => $e->getCode(),
                'hint'    => $hint ?: 'Check Twilio dashboard for details.',
            ], 500);
        }
    }

    /**
     * Get recent WhatsApp logs.
     *
     * GET /api/whatsapp/logs?limit=20
     */
    public function logs(Request $request): \Illuminate\Http\JsonResponse
    {
        $limit = $request->input('limit', 20);

        try {
            $logs = WhatsAppLog::orderByDesc('created_at')
                ->limit($limit)
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $logs,
                'total'   => WhatsAppLog::count(),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
                'hint'    => 'whatsapp_logs table might not exist. Run: php artisan migrate',
            ], 500);
        }
    }
}
