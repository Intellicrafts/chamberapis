<?php
/**
 * ============================================================
 *  MeraVakil — WhatsApp Staging Diagnostic Script
 *  Usage (on staging server): php diagnose_whatsapp.php
 *  DELETE THIS FILE after debugging is done.
 * ============================================================
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║    MeraVakil — WhatsApp Staging Diagnostics         ║\n";
echo "║    " . now()->toDateTimeString() . "                 ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$allOk = true;

// ──────────────────────────────────────────────────────────────
// CHECK 1 — Environment basics
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 1: Environment\n";
echo "  APP_ENV         : " . config('app.env') . "\n";
echo "  APP_DEBUG       : " . (config('app.debug') ? 'true' : 'false') . "\n";
echo "  QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "  CACHE_STORE     : " . config('cache.default') . "\n\n";

if (config('queue.default') === 'database') {
    echo "  ⚠️  QUEUE_CONNECTION=database\n";
    echo "     Events using ShouldQueue will NOT fire without a queue worker!\n";
    echo "     WhatsApp now calls Twilio directly (no queue), so this is OK.\n\n";
}

// ──────────────────────────────────────────────────────────────
// CHECK 2 — Twilio credentials
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 2: Twilio Configuration\n";
$sid   = config('services.twilio.sid');
$token = config('services.twilio.token');
$from  = config('services.twilio.whatsapp_from');

$twilioOk = true;
if (empty($sid)) {
    echo "  ❌ TWILIO_ACCOUNT_SID is MISSING\n"; $twilioOk = false; $allOk = false;
} else {
    echo "  ✅ TWILIO_ACCOUNT_SID : " . substr($sid, 0, 8) . "****\n";
}
if (empty($token)) {
    echo "  ❌ TWILIO_AUTH_TOKEN is MISSING\n"; $twilioOk = false; $allOk = false;
} else {
    echo "  ✅ TWILIO_AUTH_TOKEN  : " . substr($token, 0, 8) . "****\n";
}
if (empty($from)) {
    echo "  ❌ TWILIO_WHATSAPP_FROM is MISSING\n"; $twilioOk = false; $allOk = false;
} else {
    echo "  ✅ TWILIO_WHATSAPP_FROM: $from\n";
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// CHECK 3 — Database connectivity
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 3: Database Connection\n";
try {
    DB::connection()->getPdo();
    echo "  ✅ MySQL connected: " . config('database.connections.mysql.database') . "\n";
} catch (\Throwable $e) {
    echo "  ❌ DB Connection FAILED: " . $e->getMessage() . "\n";
    $allOk = false;
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// CHECK 4 — whatsapp_logs table exists
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 4: whatsapp_logs table\n";
$logsTableExists = false;
try {
    if (Schema::hasTable('whatsapp_logs')) {
        $count = DB::table('whatsapp_logs')->count();
        echo "  ✅ Table EXISTS — $count records\n";
        $logsTableExists = true;

        // Show recent log entries
        $recent = DB::table('whatsapp_logs')->orderByDesc('created_at')->limit(5)->get();
        if ($recent->count() > 0) {
            echo "  Recent logs:\n";
            foreach ($recent as $r) {
                $icon = $r->status === 'sent' ? '✅' : '❌';
                echo "    $icon [{$r->created_at}] {$r->message_type} → {$r->phone} [{$r->status}]\n";
            }
        } else {
            echo "  ℹ️  No log entries yet — this means WhatsApp has NEVER been attempted on staging\n";
        }
    } else {
        echo "  ❌ TABLE MISSING — migration was NOT run on staging!\n";
        echo "     Run: php artisan migrate\n";
        $allOk = false;
    }
} catch (\Throwable $e) {
    echo "  ❌ Error checking table: " . $e->getMessage() . "\n";
    $allOk = false;
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// CHECK 5 — User phone numbers in DB
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 5: User Phone Numbers in DB\n";
try {
    $users = DB::table('users')->select('id', 'name', 'phone')->limit(10)->get();
    $withPhone = 0;
    foreach ($users as $u) {
        $hasPhone = !empty(trim((string)($u->phone ?? '')));
        $icon = $hasPhone ? '✅' : '❌';
        echo "  $icon User #{$u->id} [{$u->name}]: " . ($hasPhone ? $u->phone : 'NO PHONE NUMBER') . "\n";
        if ($hasPhone) $withPhone++;
    }
    if ($withPhone === 0) {
        echo "  ⚠️  NONE of your users have phone numbers — WhatsApp cannot send!\n";
        $allOk = false;
    }
} catch (\Throwable $e) {
    echo "  ❌ Error reading users: " . $e->getMessage() . "\n";
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// CHECK 6 — Lawyer phone numbers in DB
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 6: Lawyer Phone Numbers in DB\n";
try {
    $lawyers = DB::table('lawyers')->select('id', 'full_name', 'phone_number')->limit(10)->get();
    if ($lawyers->count() === 0) {
        echo "  ⚠️  No lawyers found in DB\n";
    }
    $withPhone = 0;
    foreach ($lawyers as $l) {
        $hasPhone = !empty(trim((string)($l->phone_number ?? '')));
        $icon = $hasPhone ? '✅' : '❌';
        echo "  $icon Lawyer #{$l->id} [{$l->full_name}]: " . ($hasPhone ? $l->phone_number : 'NO PHONE NUMBER') . "\n";
        if ($hasPhone) $withPhone++;
    }
    if ($lawyers->count() > 0 && $withPhone === 0) {
        echo "  ⚠️  NONE of your lawyers have phone numbers — WhatsApp cannot send!\n";
        $allOk = false;
    }
} catch (\Throwable $e) {
    echo "  ❌ Error reading lawyers: " . $e->getMessage() . "\n";
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// CHECK 7 — Event → Listener registration
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 7: Event Listener Registration\n";
$dispatcher = app('events');
$expectedEvents = [
    \App\Events\AppointmentBooked::class       => \App\Listeners\SendAppointmentWhatsAppNotification::class,
    \App\Events\UserJoinedConsultation::class   => \App\Listeners\SendJoinAlertWhatsAppNotification::class,
    \App\Events\ConsultationSessionEnded::class => \App\Listeners\SendSessionEndedWhatsAppNotification::class,
];
// Check event cache file
$cachedPath = base_path('bootstrap/cache/events.php');
if (file_exists($cachedPath)) {
    echo "  ℹ️  Event cache file found: bootstrap/cache/events.php\n";
    $cachedEvents = include $cachedPath;
    foreach ($expectedEvents as $event => $listener) {
        $eventName   = class_basename($event);
        $listenerName= class_basename($listener);
        $found = isset($cachedEvents[$event]) && in_array($listener, $cachedEvents[$event]);
        $icon = $found ? '✅' : '❌';
        echo "  $icon $eventName → $listenerName\n";
        if (!$found) $allOk = false;
    }
} else {
    echo "  ⚠️  No event cache — checking EventServiceProvider directly\n";
    // Try to reflect the provider
    try {
        $provider = new \App\Providers\EventServiceProvider(app());
        $listen = (new \ReflectionClass($provider))->getProperty('listen');
        $listen->setAccessible(true);
        $registeredListeners = $listen->getValue($provider);

        foreach ($expectedEvents as $event => $listener) {
            $eventName    = class_basename($event);
            $listenerName  = class_basename($listener);
            $found = isset($registeredListeners[$event]) &&
                     in_array($listener, $registeredListeners[$event]);
            $icon = $found ? '✅' : '❌';
            echo "  $icon $eventName → $listenerName\n";
            if (!$found) $allOk = false;
        }
    } catch (\Throwable $e) {
        echo "  ❌ Cannot inspect EventServiceProvider: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// CHECK 8 — LIVE Twilio test (sends a real message)
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 8: Live Twilio API Test\n";
if (!$twilioOk) {
    echo "  ⏭  Skipped — Twilio credentials missing (see CHECK 2)\n";
} else {
    echo "  Attempting to send test WhatsApp to +919557824745 ...\n";
    try {
        $service = app(\App\Services\WhatsAppService::class);
        $msgSid = $service->send(
            '+919557824745',
            "🏛️ *MeraVakil Chambers*\n\n✅ *Staging Diagnostic Test*\n\nWhatsApp from staging is WORKING!\nTime: " . now()->toDateTimeString() . "\n\n_Auto-sent by diagnostic script_",
            'diagnostic_test',
            null,
            true  // throwOnError = true so we see exact failure
        );
        echo "  ✅ SUCCESS! Twilio SID: $msgSid\n";
        echo "  ✅ Message sent to +919557824745 — check your phone!\n";
    } catch (\Twilio\Exceptions\RestException $e) {
        echo "  ❌ Twilio API Error [Code {$e->getCode()}]: " . $e->getMessage() . "\n";

        // Decode common Twilio error codes
        switch ($e->getCode()) {
            case 20003:
                echo "  💡 FIX: Twilio credentials are WRONG (SID or Token incorrect)\n";
                break;
            case 21608:
                echo "  💡 FIX: This number has NOT opted in to the Twilio sandbox!\n";
                echo "     The recipient must WhatsApp 'join [keyword]' to +14155238886 first.\n";
                break;
            case 21211:
            case 21612:
                echo "  💡 FIX: The 'To' phone number format is invalid\n";
                break;
            case 21614:
                echo "  💡 FIX: Phone number is not capable of receiving WhatsApp messages\n";
                break;
            default:
                echo "  💡 Check https://www.twilio.com/docs/errors/{$e->getCode()}\n";
        }
        $allOk = false;
    } catch (\Throwable $e) {
        echo "  ❌ Unexpected Error: " . $e->getMessage() . "\n";
        echo "  Trace:\n";
        $lines = explode("\n", $e->getTraceAsString());
        foreach (array_slice($lines, 0, 5) as $line) {
            echo "    $line\n";
        }
        $allOk = false;
    }
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// CHECK 9 — Laravel log file (last 20 lines for errors)
// ──────────────────────────────────────────────────────────────
echo "▶ CHECK 9: Recent Laravel Log (WhatsApp-related errors)\n";
$logPath = storage_path('logs/laravel.log');
if (file_exists($logPath)) {
    $lines = file($logPath);
    $total = count($lines);
    $start = max(0, $total - 80);
    $relevant = [];
    for ($i = $start; $i < $total; $i++) {
        if (stripos($lines[$i], 'whatsapp') !== false ||
            stripos($lines[$i], 'twilio') !== false ||
            stripos($lines[$i], 'AppointmentBooked') !== false ||
            stripos($lines[$i], 'SendAppointment') !== false) {
            $relevant[] = rtrim($lines[$i]);
        }
    }
    if (empty($relevant)) {
        echo "  ℹ️  No WhatsApp/Twilio entries found in last 80 log lines.\n";
        echo "  This means the WhatsApp code is NOT being reached at all.\n";
    } else {
        echo "  Found " . count($relevant) . " relevant log entries:\n";
        foreach ($relevant as $line) {
            echo "    " . substr($line, 0, 200) . "\n";
        }
    }
} else {
    echo "  ⚠️  Log file not found at: $logPath\n";
}
echo "\n";

// ──────────────────────────────────────────────────────────────
// SUMMARY
// ──────────────────────────────────────────────────────────────
echo "╔══════════════════════════════════════════════════════╗\n";
if ($allOk) {
    echo "║  ✅ ALL CHECKS PASSED — WhatsApp should be working  ║\n";
} else {
    echo "║  ❌ ISSUES FOUND — see ❌ items above for fixes     ║\n";
}
echo "╚══════════════════════════════════════════════════════╝\n\n";
echo "⚠️  Remember to DELETE this file after debugging!\n\n";
