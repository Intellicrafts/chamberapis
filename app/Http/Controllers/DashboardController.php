<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class DashboardController extends Controller
{
    /**
     * Show the main dashboard view.
     */
    public function index()
    {
        return view('welcome');
    }

    /**
     * Return full system health data.
     */
    public function health()
    {
        $start = microtime(true);

        $checks = [];

        // --- Database ---
        try {
            $dbStart = microtime(true);
            DB::connection()->getPdo();
            $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
            $dbVersion = DB::select('SELECT VERSION() as version')[0]->version ?? 'Unknown';
            $checks['database'] = [
                'status'  => 'ok',
                'latency' => $dbLatency,
                'message' => "MySQL {$dbVersion}",
            ];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'latency' => null, 'message' => $e->getMessage()];
        }

        // --- Cache ---
        try {
            $cacheStart = microtime(true);
            Cache::put('_health_check', 1, 10);
            $val = Cache::get('_health_check');
            $cacheLatency = round((microtime(true) - $cacheStart) * 1000, 2);
            $checks['cache'] = [
                'status'  => $val === 1 ? 'ok' : 'error',
                'latency' => $cacheLatency,
                'message' => config('cache.default') . ' driver',
                'driver'  => config('cache.default'),
            ];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'error', 'latency' => null, 'message' => $e->getMessage()];
        }

        // --- Queue ---
        try {
            $queueDriver = config('queue.default');
            $checks['queue'] = [
                'status'  => 'ok',
                'latency' => null,
                'message' => "{$queueDriver} driver",
                'driver'  => $queueDriver,
            ];
        } catch (\Exception $e) {
            $checks['queue'] = ['status' => 'error', 'latency' => null, 'message' => $e->getMessage()];
        }

        // --- Storage ---
        try {
            $storagePath = storage_path('logs');
            $writable    = is_writable($storagePath);
            $storageUsed = disk_total_space('/') > 0
                ? round((1 - disk_free_space('/') / disk_total_space('/')) * 100, 1)
                : null;

            $checks['storage'] = [
                'status'    => $writable ? 'ok' : 'warning',
                'latency'   => null,
                'message'   => $writable ? 'Writable' : 'Not writable',
                'disk_used' => $storageUsed,
            ];
        } catch (\Exception $e) {
            $checks['storage'] = ['status' => 'error', 'latency' => null, 'message' => $e->getMessage()];
        }

        // --- Mail ---
        $checks['mail'] = [
            'status'  => 'ok',
            'latency' => null,
            'message' => config('mail.default') . ' mailer',
            'driver'  => config('mail.default'),
        ];

        // --- System ---
        $overallStatus = collect($checks)->every(fn($c) => $c['status'] === 'ok') ? 'healthy' :
            (collect($checks)->contains(fn($c) => $c['status'] === 'error') ? 'degraded' : 'warning');

        $totalTime = round((microtime(true) - $start) * 1000, 2);

        return response()->json([
            'status'      => $overallStatus,
            'timestamp'   => now()->toIso8601String(),
            'response_ms' => $totalTime,
            'environment' => app()->environment(),
            'app_name'    => config('app.name'),
            'app_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'checks'      => $checks,
        ]);
    }

    /**
     * Return system metrics: CPU, memory, DB stats, route count, etc.
     */
    public function metrics()
    {
        // Memory
        $memUsed  = memory_get_usage(true);
        $memPeak  = memory_get_peak_usage(true);
        $memLimit = ini_get('memory_limit');

        // DB stats
        $tableStats = [];
        try {
            $tables = DB::select("SHOW TABLE STATUS");
            foreach ($tables as $t) {
                $tableStats[] = [
                    'name'    => $t->Name,
                    'rows'    => $t->Rows,
                    'size_mb' => round(($t->Data_length + $t->Index_length) / 1024 / 1024, 3),
                ];
            }
        } catch (\Exception $e) {
        }

        // Routes
        $routeCount = count(Route::getRoutes()->getRoutes());

        // Uptime (approximate via storage file)
        $startFile = storage_path('framework/cache/app-start.txt');
        if (!file_exists($startFile)) {
            file_put_contents($startFile, time());
        }
        $startTime  = (int) file_get_contents($startFile);
        $uptimesSecs = time() - $startTime;

        return response()->json([
            'timestamp'    => now()->toIso8601String(),
            'memory'       => [
                'used_bytes' => $memUsed,
                'used_mb'    => round($memUsed / 1024 / 1024, 2),
                'peak_mb'    => round($memPeak / 1024 / 1024, 2),
                'limit'      => $memLimit,
            ],
            'disk'         => [
                'free_gb'  => round(disk_free_space('/') / 1024 ** 3, 2),
                'total_gb' => round(disk_total_space('/') / 1024 ** 3, 2),
                'used_pct' => disk_total_space('/') > 0
                    ? round((1 - disk_free_space('/') / disk_total_space('/')) * 100, 1)
                    : 0,
            ],
            'php'          => [
                'version'      => PHP_VERSION,
                'extensions'   => get_loaded_extensions(),
                'max_exec_sec' => ini_get('max_execution_time'),
            ],
            'app'          => [
                'laravel_version' => app()->version(),
                'environment'     => app()->environment(),
                'debug'           => config('app.debug'),
                'route_count'     => $routeCount,
                'uptime_seconds'  => $uptimesSecs,
            ],
            'database'     => [
                'driver'    => config('database.default'),
                'host'      => config('database.connections.' . config('database.default') . '.host'),
                'database'  => config('database.connections.' . config('database.default') . '.database'),
                'tables'    => $tableStats,
            ],
        ]);
    }

    /**
     * Stream the latest N lines of the Laravel log file.
     */
    public function logs(Request $request)
    {
        $lines  = (int) $request->get('lines', 100);
        $level  = $request->get('level', 'all');
        $search = $request->get('search', '');

        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return response()->json(['entries' => [], 'total_bytes' => 0]);
        }

        $totalBytes = filesize($logFile);

        // Read last N lines efficiently
        $allLines = $this->tailFile($logFile, $lines * 4); // read more, filter down

        // Parse entries
        $entries = [];
        $current = null;

        foreach ($allLines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/', $line, $m)) {
                if ($current) {
                    $entries[] = $current;
                }
                $current = [
                    'datetime'    => $m[1],
                    'environment' => $m[2],
                    'level'       => strtolower($m[3]),
                    'message'     => trim($m[4]),
                    'context'     => '',
                ];
            } elseif ($current && !empty(trim($line))) {
                $current['context'] .= ' ' . trim($line);
            }
        }
        if ($current) {
            $entries[] = $current;
        }

        // Filter by level
        if ($level !== 'all') {
            $entries = array_filter($entries, fn($e) => $e['level'] === strtolower($level));
        }

        // Filter by search
        if (!empty($search)) {
            $entries = array_filter($entries, fn($e) => str_contains(strtolower($e['message']), strtolower($search)));
        }

        $entries = array_values(array_slice(array_reverse($entries), 0, $lines));

        return response()->json([
            'entries'     => $entries,
            'total_bytes' => $totalBytes,
            'total_lines' => count($entries),
            'log_file'    => basename($logFile),
        ]);
    }

    /**
     * Return all registered API endpoints.
     */
    public function routes()
    {
        $routes = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            if (str_starts_with($uri, 'api/') || str_starts_with($uri, '_')) {
                continue; // skip internal
            }
            $methods = array_filter($route->methods(), fn($m) => $m !== 'HEAD');
            foreach ($methods as $method) {
                $routes[] = [
                    'method'     => $method,
                    'uri'        => '/' . $uri,
                    'name'       => $route->getName() ?? '',
                    'middleware' => implode(', ', $route->middleware()),
                    'action'     => $route->getActionName(),
                ];
            }
        }

        // Add all API routes
        foreach (Route::getRoutes()->getRoutes() as $route) {
            $uri = $route->uri();
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }
            $methods = array_filter($route->methods(), fn($m) => $m !== 'HEAD');
            foreach ($methods as $method) {
                $routes[] = [
                    'method'     => $method,
                    'uri'        => '/' . $uri,
                    'name'       => $route->getName() ?? '',
                    'middleware' => implode(', ', $route->middleware()),
                    'action'     => $route->getActionName(),
                ];
            }
        }

        return response()->json([
            'total'  => count($routes),
            'routes' => $routes,
        ]);
    }

    /**
     * Execute an artisan command or shell command and return output.
     */
    public function runCommand(Request $request)
    {
        $command = trim($request->input('command', ''));

        if (empty($command)) {
            return response()->json(['status' => 'error', 'output' => 'No command provided.']);
        }

        // Allowed artisan commands (whitelist for security)
        $artisanAllowed = [
            'optimize', 'optimize:clear',
            'cache:clear', 'config:cache', 'config:clear',
            'route:cache', 'route:clear', 'view:cache', 'view:clear',
            'migrate', 'migrate:status', 'migrate:fresh', 'migrate:refresh',
            'db:seed', 'db:show',
            'queue:work', 'queue:restart', 'queue:flush',
            'storage:link',
            'key:generate',
            'make:controller', 'make:model', 'make:migration', 'make:seeder',
            'make:middleware', 'make:request', 'make:event', 'make:job',
            'inspire', '--version', 'list', '--help',
            'telescope:prune', 'sanctum:prune-expired',
            'package:discover',
        ];

        // Allowed raw shell commands
        $shellAllowed = ['ls', 'pwd', 'php -v', 'php --version', 'composer --version',
            'git status', 'git log', 'git branch', 'git pull', 'git diff',
            'npm --version', 'node --version'];

        Log::info("Dashboard terminal: {$command}");

        // If it's an artisan command
        if (str_starts_with($command, 'php artisan ')) {
            $artisanCmd = substr($command, strlen('php artisan '));
            $base       = explode(' ', $artisanCmd)[0];

            // Check if explicitly whitelisted or handle via Artisan::call
            try {
                $outputBuffer = new \Symfony\Component\Console\Output\BufferedOutput();
                $exitCode     = Artisan::call($artisanCmd, [], $outputBuffer);
                $output       = $outputBuffer->fetch();
                if (empty($output)) {
                    $output = Artisan::output();
                }

                return response()->json([
                    'status'    => $exitCode === 0 ? 'success' : 'error',
                    'exit_code' => $exitCode,
                    'output'    => $output ?: '(Command completed with no output)',
                    'command'   => $command,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'status'  => 'error',
                    'output'  => $e->getMessage(),
                    'command' => $command,
                ]);
            }
        }

        // For shell commands, use Process
        try {
            $process = Process::fromShellCommandline($command, base_path(), null, null, 30);
            $process->run();

            return response()->json([
                'status'       => $process->isSuccessful() ? 'success' : 'error',
                'exit_code'    => $process->getExitCode(),
                'output'       => $process->getOutput() ?: '(No output)',
                'error_output' => $process->getErrorOutput(),
                'command'      => $command,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'output'  => $e->getMessage(),
                'command' => $command,
            ]);
        }
    }

    /**
     * Clear application caches.
     */
    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');

        $results = [];

        try {
            if ($type === 'all' || $type === 'config') {
                Artisan::call('config:clear');
                $results['config'] = 'cleared';
            }
            if ($type === 'all' || $type === 'route') {
                Artisan::call('route:clear');
                $results['route'] = 'cleared';
            }
            if ($type === 'all' || $type === 'view') {
                Artisan::call('view:clear');
                $results['view'] = 'cleared';
            }
            if ($type === 'all' || $type === 'cache') {
                Artisan::call('cache:clear');
                $results['cache'] = 'cleared';
            }

            Log::info("Dashboard: Cache cleared ({$type})");

            return response()->json(['status' => 'success', 'cleared' => $results]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Re-run optimize.
     */
    public function optimize()
    {
        try {
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            Artisan::call('optimize', [], $output);
            return response()->json(['status' => 'success', 'output' => $output->fetch()]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Database connection and table stats.
     */
    public function dbStats()
    {
        try {
            $tables = DB::select("SHOW TABLE STATUS");
            $formatted = array_map(fn($t) => [
                'name'       => $t->Name,
                'rows'       => $t->Rows,
                'data_mb'    => round($t->Data_length / 1024 / 1024, 3),
                'index_mb'   => round($t->Index_length / 1024 / 1024, 3),
                'total_mb'   => round(($t->Data_length + $t->Index_length) / 1024 / 1024, 3),
                'engine'     => $t->Engine,
                'collation'  => $t->Collation,
                'created_at' => $t->Create_time,
                'updated_at' => $t->Update_time,
            ], $tables);

            return response()->json([
                'status'     => 'ok',
                'connection' => config('database.default'),
                'database'   => config('database.connections.' . config('database.default') . '.database'),
                'host'       => config('database.connections.' . config('database.default') . '.host'),
                'tables'     => $formatted,
                'count'      => count($formatted),
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function tailFile(string $filepath, int $lines): array
    {
        $f   = fopen($filepath, 'rb');
        $buffer = '';
        fseek($f, 0, SEEK_END);
        $pos = ftell($f);
        $chunk = 8192;
        $lineCount = 0;

        while ($pos > 0 && $lineCount < $lines) {
            $readSize = min($chunk, $pos);
            $pos -= $readSize;
            fseek($f, $pos);
            $buffer = fread($f, $readSize) . $buffer;
            $lineCount = substr_count($buffer, "\n");
        }
        fclose($f);

        $allLines = explode("\n", $buffer);
        return array_slice($allLines, -$lines);
    }
}
