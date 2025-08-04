<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GitDeployController extends Controller
{
    public function deploy(Request $request, $repo)
    {
        $secret = env('GIT_WEBHOOK_SECRET');
        $header = $request->header('X-Hub-Signature-256');
        
        if ($secret && $header) {
            $payload = $request->getContent();
            $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($expected, $header)) {
                Log::warning('Invalid webhook signature');
                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        // Check if push was to main branch
        $branchRef = $request->input('ref'); // e.g., "refs/heads/main"
        if ($branchRef !== 'refs/heads/main') {
            return response()->json(['message' => 'Not main branch'], 200);
        }

        // Run the pull command
        if($repo == 'v-sangam-api') {
            $pathToRepo = base_path(); // or specify full path like '/var/www/project'
        }else if($repo == 'v-sangam-build') {
            $pathToRepo = realpath(base_path('../vsangam.logicera.in')); // Adjust path as needed
        } else {
            return response()->json(['message' => 'Repository not found'], 404);
        }
        $process = Process::fromShellCommandline('git pull origin main', $pathToRepo);
        $process->run();

        // Log and return output
        if (!$process->isSuccessful()) {
            Log::error('Deployment failed: ' . $process->getErrorOutput());
            return response()->json(['message' => 'Deployment failed'], 500);
        }

        Log::info('Deployment successful: ' . $process->getOutput());
        return response()->json([
            'message' => 'Deployment successful',
            'output' => $process->getOutput()
        ]);
    }

    public function optimize()
    {
        $process = Process::fromShellCommandline('php artisan optimize');
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error('Optimization failed: ' . $process->getErrorOutput());
            return response()->json(['message' => 'Optimization failed'], 500);
        }

        Log::info('Optimization successful: ' . $process->getOutput());
        return response()->json([
            'message' => 'Optimization successful',
            'output' => $process->getOutput()
        ]);
    }
}
