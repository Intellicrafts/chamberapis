<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CommandController extends Controller
{
    /**
     * Pull the latest changes from the main branch
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pullFromMain(Request $request)
    {
        try {
            // Execute git pull command for main branch
            $process = Process::fromShellCommandline('git pull origin main');
            $process->setWorkingDirectory(base_path()); // Set working directory to Laravel root
            $process->setTimeout(120); // Increase timeout for potentially larger pulls
            $process->run();
            
            // Get the output and error output
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Prepare the response
            $response = [
                'status' => $exitCode === 0 ? 'success' : 'error',
                'exit_code' => $exitCode,
                'command' => 'git pull origin main',
                'output' => $output,
            ];

            // Add error output if there is any
            if (!empty($errorOutput)) {
                $response['error_output'] = $errorOutput;
            }

            return response()->json($response);
        } catch (ProcessFailedException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to pull from main branch.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Fix common typos in commands
     *
     * @param string $command
     * @return string
     */
    private function fixCommonTypos($command)
    {
        $typos = [
            'php artisan optmize' => 'php artisan optimize',
            'php artisan optimze' => 'php artisan optimize',
            'php artisan optimise' => 'php artisan optimize',
            'php artisan config:cashe' => 'php artisan config:cache',
            'php artisan config:chache' => 'php artisan config:cache',
            'php artisan serve --port=' => 'php artisan serve --port=',
            'php artisan migarte' => 'php artisan migrate',
            'php artisan migate' => 'php artisan migrate',
            'php artisan miggrate' => 'php artisan migrate',
            'composer instal' => 'composer install',
            'composer updaet' => 'composer update',
            'composer updte' => 'composer update',
            'git pul' => 'git pull',
            'git comit' => 'git commit',
            'git checout' => 'git checkout',
            'git sttaus' => 'git status',
            'git statsu' => 'git status',
        ];
        
        foreach ($typos as $typo => $correction) {
            if (strpos($command, $typo) === 0) {
                return str_replace($typo, $correction, $command);
            }
        }
        
        return $command;
    }

    public function executeCommand(Request $request, $command = null)
    {
        // Log the request for debugging
        \Log::info('Command execution request', [
            'url_command' => $command,
            'request_has_command' => $request->has('command'),
            'request_command' => $request->input('command'),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'request_url' => $request->url(),
            'request_all' => $request->all(),
        ]);
        
        // If no command is provided in the URL, check if it's in the request
        if (empty($command) && $request->has('command')) {
            $command = $request->input('command');
        }

        // If still no command, return an error
        if (!$command) {
            return response()->json([
                'status' => 'error',
                'message' => 'No command provided.',
            ], 400);
        }

        // Decode URL-encoded command
        $command = urldecode($command);

        // Whitelist of allowed commands
        $allowedCommands = [
            'php artisan',
            'git',
            'composer',
            'npm',
            'ls',
            'cat',
            'echo',
            'tail',
            'grep'
        ];

        // Check if the command starts with any of the allowed commands
        $isAllowed = false;
        foreach ($allowedCommands as $allowedCommand) {
            if (strpos($command, $allowedCommand) === 0) {
                $isAllowed = true;
                break;
            }
        }

        // If command is not allowed, return an error
        if (!$isAllowed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Command not allowed for security reasons.',
                'allowed_commands' => $allowedCommands
            ], 403);
        }

        try {
            // Check for common typos and fix them
            $fixedCommand = $this->fixCommonTypos($command);
            
            // If command was fixed, inform the user
            $commandWasCorrected = ($fixedCommand !== $command);
            $originalCommand = $command;
            $command = $fixedCommand;
            
            // Execute the command
            $process = Process::fromShellCommandline($command);
            $process->setWorkingDirectory(base_path()); // Set working directory to Laravel root
            $process->setTimeout(60);
            $process->run();
            
            // Get the output and error output
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Prepare the response
            $response = [
                'status' => $exitCode === 0 ? 'success' : 'error',
                'exit_code' => $exitCode,
                'command' => $command,
                'output' => $output,
            ];

            // Add error output if there is any
            if (!empty($errorOutput)) {
                $response['error_output'] = $errorOutput;
            }
            
            // Add information about corrected command
            if (isset($commandWasCorrected) && $commandWasCorrected) {
                $response['command_corrected'] = true;
                $response['original_command'] = $originalCommand;
                $response['output'] = "Command was corrected from '{$originalCommand}' to '{$command}'.\n\n" . $response['output'];
            }

            return response()->json($response);
        } catch (ProcessFailedException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to execute command.',
                'command' => $command,
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Render a view to display command execution results
     *
     * @param Request $request
     * @param string $command The command to execute
     * @return \Illuminate\Http\Response
     */
    public function executeCommandView(Request $request, $command = null)
    {
        // If no command is provided in the URL, check if it's in the request
        if (!$command && $request->has('command')) {
            $command = $request->input('command');
        }

        // If still no command, return an error
        if (!$command) {
            return view('command.error', [
                'error' => 'No command provided.'
            ]);
        }

        // Decode URL-encoded command
        $command = urldecode($command);

        // Whitelist of allowed commands
        $allowedCommands = [
            'php artisan',
            'git',
            'composer',
            'npm',
            'ls',
            'cat',
            'echo',
            'tail',
            'grep'
        ];

        // Check if the command starts with any of the allowed commands
        $isAllowed = false;
        foreach ($allowedCommands as $allowedCommand) {
            if (strpos($command, $allowedCommand) === 0) {
                $isAllowed = true;
                break;
            }
        }

        // If command is not allowed, return an error
        if (!$isAllowed) {
            return view('command.error', [
                'error' => 'Command not allowed for security reasons.',
                'allowed_commands' => $allowedCommands
            ]);
        }

        try {
            // Check for common typos and fix them
            $fixedCommand = $this->fixCommonTypos($command);
            
            // If command was fixed, inform the user
            $commandWasCorrected = ($fixedCommand !== $command);
            $originalCommand = $command;
            $command = $fixedCommand;
            
            // Execute the command
            $process = Process::fromShellCommandline($command);
            $process->setWorkingDirectory(base_path()); // Set working directory to Laravel root
            $process->setTimeout(60);
            $process->run();
            
            // Get the output and error output
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Prepare the data for the view
            $data = [
                'status' => $exitCode === 0 ? 'success' : 'error',
                'exit_code' => $exitCode,
                'command' => $command,
                'output' => $output,
                'error_output' => $errorOutput,
            ];
            
            // Add information about corrected command
            if (isset($commandWasCorrected) && $commandWasCorrected) {
                $data['command_corrected'] = true;
                $data['original_command'] = $originalCommand;
                $data['output'] = "Command was corrected from '{$originalCommand}' to '{$command}'.\n\n" . $data['output'];
            }

            return view('command.result', $data);
        } catch (ProcessFailedException $exception) {
            return view('command.error', [
                'error' => 'Failed to execute command: ' . $exception->getMessage(),
                'command' => $command
            ]);
        }
    }
    
    /**
     * Render a view to display the result of pulling from main branch
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pullFromMainView(Request $request)
    {
        try {
            // Execute git pull command for main branch
            $process = Process::fromShellCommandline('git pull origin main');
            $process->setWorkingDirectory(base_path()); // Set working directory to Laravel root
            $process->setTimeout(120); // Increase timeout for potentially larger pulls
            $process->run();
            
            // Get the output and error output
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            // Prepare the data for the view
            $data = [
                'status' => $exitCode === 0 ? 'success' : 'error',
                'exit_code' => $exitCode,
                'command' => 'git pull origin main',
                'output' => $output,
                'error_output' => $errorOutput,
            ];

            return view('command.result', $data);
        } catch (ProcessFailedException $exception) {
            return view('command.error', [
                'error' => 'Failed to pull from main branch: ' . $exception->getMessage(),
                'command' => 'git pull origin main'
            ]);
        }
    }
    
    /**
     * Display the interactive terminal interface
     *
     * @return \Illuminate\Http\Response
     */
    public function terminal()
    {
        return view('command.terminal');
    }
}