<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakil API Terminal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Fira+Code:wght@300;400;500;600;700&display=swap');
        
        .terminal {
            background-color: #1e1e2e;
            color: #cdd6f4;
            font-family: 'Fira Code', monospace;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .terminal-header {
            background-color: #181825;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #313244;
        }
        
        .terminal-controls {
            display: flex;
            gap: 8px;
        }
        
        .terminal-control {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .terminal-close { background-color: #f38ba8; }
        .terminal-minimize { background-color: #f9e2af; }
        .terminal-maximize { background-color: #a6e3a1; }
        
        .terminal-title {
            font-size: 14px;
            font-weight: 500;
        }
        
        .terminal-body {
            padding: 16px;
            min-height: 300px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .terminal-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .terminal-body::-webkit-scrollbar-track {
            background: #181825;
        }
        
        .terminal-body::-webkit-scrollbar-thumb {
            background: #313244;
            border-radius: 4px;
        }
        
        .terminal-body::-webkit-scrollbar-thumb:hover {
            background: #45475a;
        }
        
        .prompt {
            color: #89b4fa;
            font-weight: 500;
        }
        
        .command-input {
            background-color: transparent;
            border: none;
            color: #cdd6f4;
            font-family: 'Fira Code', monospace;
            font-size: 14px;
            outline: none;
            width: 100%;
            caret-color: #f5c2e7;
        }
        
        .command-output {
            margin-top: 8px;
            margin-bottom: 16px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        
        .success-output {
            color: #a6e3a1;
        }
        
        .error-output {
            color: #f38ba8;
        }
        
        .command-history {
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 1px dashed #313244;
        }
        
        .command-history-item {
            display: flex;
            margin-bottom: 4px;
        }
        
        .command-history-prompt {
            color: #89b4fa;
            margin-right: 8px;
        }
        
        .command-history-text {
            color: #cdd6f4;
        }
        
        .environment-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .env-local {
            background-color: #89b4fa;
            color: #1e1e2e;
        }
        
        .env-production {
            background-color: #f38ba8;
            color: #1e1e2e;
        }
        
        .env-staging {
            background-color: #f9e2af;
            color: #1e1e2e;
        }
        
        .env-development {
            background-color: #a6e3a1;
            color: #1e1e2e;
        }
        
        .suggestion {
            color: #f5c2e7;
            margin-top: 4px;
            font-style: italic;
        }
        
        .command-suggestions {
            margin-top: 8px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .suggestion-item {
            background-color: #313244;
            color: #cdd6f4;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .suggestion-item:hover {
            background-color: #45475a;
        }
        
        .terminal-footer {
            background-color: #181825;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #313244;
            font-size: 12px;
        }
        
        .terminal-status {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-online {
            background-color: #a6e3a1;
        }
        
        .status-offline {
            background-color: #f38ba8;
        }
        
        .command-autocomplete {
            position: absolute;
            background-color: #313244;
            border: 1px solid #45475a;
            border-radius: 4px;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 10;
            display: none;
        }
        
        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
        }
        
        .autocomplete-item:hover, .autocomplete-item.selected {
            background-color: #45475a;
        }
        
        .command-help {
            margin-top: 16px;
            background-color: #313244;
            padding: 12px;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .help-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #89b4fa;
        }
        
        .help-commands {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 8px 16px;
        }
        
        .help-command {
            color: #f5c2e7;
            font-weight: 500;
        }
        
        .help-description {
            color: #cdd6f4;
        }
        
        .typing-animation::after {
            content: '|';
            animation: blink 1s step-end infinite;
        }
        
        @keyframes blink {
            from, to { opacity: 1; }
            50% { opacity: 0; }
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-5xl">
        <div class="mb-6 flex justify-between items-center">
            <h1 class="text-3xl font-bold text-white">Bakil API Terminal</h1>
            <div class="flex items-center gap-4">
                <span class="environment-badge {{ app()->environment() == 'production' ? 'env-production' : (app()->environment() == 'local' ? 'env-local' : (app()->environment() == 'staging' ? 'env-staging' : 'env-development')) }}">
                    {{ strtoupper(app()->environment()) }}
                </span>
                <span class="text-white text-sm">PHP v{{ phpversion() }} | Laravel v{{ app()->version() }}</span>
            </div>
        </div>
        
        <div class="terminal">
            <div class="terminal-header">
                <div class="terminal-controls">
                    <div class="terminal-control terminal-close"></div>
                    <div class="terminal-control terminal-minimize"></div>
                    <div class="terminal-control terminal-maximize"></div>
                </div>
                <div class="terminal-title">
                    <span id="terminal-user">bakil</span>@<span id="terminal-host">{{ gethostname() }}</span>: <span id="terminal-path">~/api</span>
                </div>
                <div class="flex items-center gap-2">
                    <button id="clear-terminal" class="text-xs text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-broom mr-1"></i> Clear
                    </button>
                    <button id="toggle-help" class="text-xs text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-question-circle mr-1"></i> Help
                    </button>
                    <button id="toggle-debug" class="text-xs text-gray-400 hover:text-white transition-colors">
                        <i class="fas fa-bug mr-1"></i> Debug
                    </button>
                </div>
            </div>
            
            <div class="terminal-body" id="terminal-body">
                <div class="command-output">
                    <div class="text-yellow-300 font-bold mb-2">Welcome to Bakil API Terminal</div>
                    <div class="mb-1">Type commands to interact with your Laravel application.</div>
                    <div class="mb-1">Type <span class="text-green-400">help</span> to see available commands.</div>
                    <div class="mb-3">Type <span class="text-green-400">clear</span> to clear the terminal.</div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <button type="button" onclick="runCommand('php artisan optimize')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            Run php artisan optimize
                        </button>
                        <button type="button" onclick="runCommand('php artisan --version')" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                            Check Laravel Version
                        </button>
                        <a href="/api/run?command=php%20artisan%20--version" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-sm">
                            Direct API Test
                        </a>
                    </div>
                </div>
                
                <div id="command-history"></div>
                
                <form id="command-form" action="/api/run" method="GET" class="w-full" onsubmit="return handleFormSubmit(event)">
                    @csrf
                    <div class="flex items-center" id="command-prompt">
                        <span class="prompt mr-2">$</span>
                        <div class="relative flex-grow">
                            <input type="text" id="command-input" name="command" class="command-input typing-animation" autofocus placeholder="Type a command..." autocomplete="off">
                            <div id="command-autocomplete" class="command-autocomplete"></div>
                        </div>
                        <button type="submit" class="ml-2 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm md:hidden">
                            <i class="fas fa-terminal"></i>
                        </button>
                    </div>
                </form>
                
                <div id="command-help" class="command-help hidden">
                    <div class="help-title">Available Commands:</div>
                    <div class="help-commands">
                        <div class="help-command">php artisan</div>
                        <div class="help-description">Run Laravel Artisan commands</div>
                        
                        <div class="help-command">git</div>
                        <div class="help-description">Execute Git commands</div>
                        
                        <div class="help-command">composer</div>
                        <div class="help-description">Manage PHP dependencies</div>
                        
                        <div class="help-command">npm</div>
                        <div class="help-description">Manage Node.js packages</div>
                        
                        <div class="help-command">ls</div>
                        <div class="help-description">List directory contents</div>
                        
                        <div class="help-command">cat</div>
                        <div class="help-description">Display file contents</div>
                        
                        <div class="help-command">clear</div>
                        <div class="help-description">Clear the terminal</div>
                        
                        <div class="help-command">help</div>
                        <div class="help-description">Show this help message</div>
                    </div>
                </div>
            </div>
            
            <div class="terminal-footer">
                <div class="terminal-status">
                    <div class="status-indicator {{ app()->environment() == 'production' ? 'status-online' : 'status-online' }}"></div>
                    <span>{{ app()->environment() == 'production' ? 'Connected to production' : 'Connected to local environment' }}</span>
                </div>
                <div class="text-gray-400">
                    <span id="current-time"></span>
                </div>
            </div>
        </div>
        
        <div class="mt-6 text-center text-gray-400 text-sm">
            <p>© {{ date('Y') }} Bakil API. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const commandInput = document.getElementById('command-input');
            const commandHistoryElement = document.getElementById('command-history');
            const terminalBody = document.getElementById('terminal-body');
            const clearTerminalBtn = document.getElementById('clear-terminal');
            const toggleHelpBtn = document.getElementById('toggle-help');
            const toggleDebugBtn = document.getElementById('toggle-debug');
            const commandHelp = document.getElementById('command-help');
            const commandAutocomplete = document.getElementById('command-autocomplete');
            const currentTimeEl = document.getElementById('current-time');
            
            // Debug mode
            let debugMode = false;
            
            // Common Laravel Artisan commands for autocomplete
            const commonCommands = [
                'php artisan serve',
                'php artisan optimize',
                'php artisan optimize:clear',
                'php artisan cache:clear',
                'php artisan config:cache',
                'php artisan config:clear',
                'php artisan route:cache',
                'php artisan route:clear',
                'php artisan view:cache',
                'php artisan view:clear',
                'php artisan migrate',
                'php artisan migrate:fresh',
                'php artisan migrate:refresh',
                'php artisan db:seed',
                'php artisan make:controller',
                'php artisan make:model',
                'php artisan make:migration',
                'php artisan make:seeder',
                'php artisan make:middleware',
                'php artisan key:generate',
                'php artisan storage:link',
                'git status',
                'git pull origin main',
                'git pull origin master',
                'git log',
                'git branch',
                'composer install',
                'composer update',
                'composer require',
                'npm install',
                'npm run dev',
                'npm run build',
                'ls -la',
                'cat .env',
                'clear',
                'help'
            ];
            
            let commandHistoryArray = [];
            let historyIndex = -1;
            let autocompleteIndex = -1;
            
            // Update current time
            function updateTime() {
                const now = new Date();
                currentTimeEl.textContent = now.toLocaleTimeString();
            }
            
            setInterval(updateTime, 1000);
            updateTime();
            
            // Focus input when clicking anywhere in the terminal
            terminalBody.addEventListener('click', function(e) {
                if (e.target.id !== 'command-input') {
                    commandInput.focus();
                }
            });
            
            // Clear terminal
            clearTerminalBtn.addEventListener('click', function() {
                commandHistoryElement.innerHTML = '';
                commandInput.focus();
            });
            
            // Toggle help
            toggleHelpBtn.addEventListener('click', function() {
                commandHelp.classList.toggle('hidden');
                terminalBody.scrollTop = terminalBody.scrollHeight;
                commandInput.focus();
            });
            
            // Toggle debug mode
            toggleDebugBtn.addEventListener('click', function() {
                debugMode = !debugMode;
                const debugMessage = document.createElement('div');
                debugMessage.className = 'command-output';
                debugMessage.innerHTML = `<div class="text-blue-400">Debug mode ${debugMode ? 'enabled' : 'disabled'}</div>`;
                commandHistoryElement.appendChild(debugMessage);
                terminalBody.scrollTop = terminalBody.scrollHeight;
                commandInput.focus();
                
                // Change button appearance
                toggleDebugBtn.className = debugMode 
                    ? 'text-xs text-green-400 hover:text-white transition-colors' 
                    : 'text-xs text-gray-400 hover:text-white transition-colors';
            });
            
            // Handle command execution
            commandInput.addEventListener('keydown', function(e) {
                // Hide autocomplete on Escape
                if (e.key === 'Escape') {
                    commandAutocomplete.style.display = 'none';
                    autocompleteIndex = -1;
                    commandInput.focus();
                    return;
                }
                
                // Navigate autocomplete with arrow keys
                if (commandAutocomplete.style.display === 'block') {
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        navigateAutocomplete(1);
                        return;
                    } else if (e.key === 'ArrowUp' && commandAutocomplete.children.length > 0) {
                        e.preventDefault();
                        navigateAutocomplete(-1);
                        return;
                    } else if (e.key === 'Tab' || e.key === 'Enter') {
                        e.preventDefault();
                        if (autocompleteIndex >= 0) {
                            selectAutocomplete(autocompleteIndex);
                        }
                        return;
                    }
                }
                
                // Command history navigation
                if (e.key === 'ArrowUp' && commandAutocomplete.style.display !== 'block') {
                    e.preventDefault();
                    if (historyIndex < commandHistoryArray.length - 1) {
                        historyIndex++;
                        commandInput.value = commandHistoryArray[commandHistoryArray.length - 1 - historyIndex];
                    }
                } else if (e.key === 'ArrowDown' && commandAutocomplete.style.display !== 'block') {
                    e.preventDefault();
                    if (historyIndex > 0) {
                        historyIndex--;
                        commandInput.value = commandHistoryArray[commandHistoryArray.length - 1 - historyIndex];
                    } else if (historyIndex === 0) {
                        historyIndex = -1;
                        commandInput.value = '';
                    }
                }
                
                // Execute command on Enter
                if (e.key === 'Enter') {
                    e.preventDefault();
                    handleFormSubmit(e);
                }
                
                // Tab completion
                if (e.key === 'Tab') {
                    e.preventDefault();
                    const input = commandInput.value.trim();
                    
                    if (input) {
                        showAutocomplete(input);
                    }
                }
            });
            
            // Show autocomplete suggestions as user types
            commandInput.addEventListener('input', function() {
                const input = commandInput.value.trim();
                
                if (input.length >= 2) {
                    showAutocomplete(input);
                } else {
                    commandAutocomplete.style.display = 'none';
                    autocompleteIndex = -1;
                }
            });
            
            // Hide autocomplete when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target !== commandAutocomplete && !commandAutocomplete.contains(e.target) && e.target !== commandInput) {
                    commandAutocomplete.style.display = 'none';
                    autocompleteIndex = -1;
                }
            });
            
            function showAutocomplete(input) {
                const matches = commonCommands.filter(cmd => cmd.toLowerCase().includes(input.toLowerCase()));
                
                if (matches.length > 0) {
                    commandAutocomplete.innerHTML = '';
                    matches.forEach((match, index) => {
                        const item = document.createElement('div');
                        item.className = 'autocomplete-item';
                        item.textContent = match;
                        item.addEventListener('click', function() {
                            commandInput.value = match;
                            commandAutocomplete.style.display = 'none';
                            commandInput.focus();
                        });
                        commandAutocomplete.appendChild(item);
                    });
                    
                    commandAutocomplete.style.display = 'block';
                    autocompleteIndex = -1;
                } else {
                    commandAutocomplete.style.display = 'none';
                    autocompleteIndex = -1;
                }
            }
            
            function navigateAutocomplete(direction) {
                const items = commandAutocomplete.children;
                
                if (items.length === 0) return;
                
                // Remove current selection
                if (autocompleteIndex >= 0) {
                    items[autocompleteIndex].classList.remove('selected');
                }
                
                // Update index
                autocompleteIndex += direction;
                
                // Handle wrapping
                if (autocompleteIndex >= items.length) {
                    autocompleteIndex = 0;
                } else if (autocompleteIndex < 0) {
                    autocompleteIndex = items.length - 1;
                }
                
                // Apply new selection
                items[autocompleteIndex].classList.add('selected');
                items[autocompleteIndex].scrollIntoView({ block: 'nearest' });
            }
            
            function selectAutocomplete(index) {
                const items = commandAutocomplete.children;
                if (index >= 0 && index < items.length) {
                    commandInput.value = items[index].textContent;
                    commandAutocomplete.style.display = 'none';
                    autocompleteIndex = -1;
                    commandInput.focus();
                }
            }
            
            function addToHistory(command) {
                const historyItem = document.createElement('div');
                historyItem.className = 'command-history-item';
                historyItem.innerHTML = `
                    <span class="command-history-prompt">$</span>
                    <span class="command-history-text">${escapeHtml(command)}</span>
                `;
                commandHistoryElement.appendChild(historyItem);
                
                // Add loading indicator
                const loadingOutput = document.createElement('div');
                loadingOutput.className = 'command-output';
                loadingOutput.innerHTML = '<div class="text-blue-400">Executing command...</div>';
                loadingOutput.id = 'loading-' + Date.now();
                commandHistoryElement.appendChild(loadingOutput);
                
                terminalBody.scrollTop = terminalBody.scrollHeight;
            }
            
            function executeCommand(command) {
                // Get the loading indicator
                const loadingId = 'loading-' + Date.now();
                const loadingOutput = document.getElementById(loadingId);
                
                // Log for debugging
                console.log('Executing command:', command);
                
                // Encode the command for URL
                const encodedCommand = encodeURIComponent(command);
                
                // Show debug info if debug mode is enabled
                if (debugMode) {
                    const debugInfo = document.createElement('div');
                    debugInfo.className = 'command-output';
                    debugInfo.innerHTML = `
                        <div class="text-yellow-400">Debug Info:</div>
                        <div class="text-gray-400">Command: ${escapeHtml(command)}</div>
                        <div class="text-gray-400">Encoded: ${escapeHtml(encodedCommand)}</div>
                        <div class="text-gray-400">URL: /api/run?command=${escapeHtml(encodedCommand)}</div>
                        <div class="text-gray-400">CSRF Token: ${document.querySelector('input[name="_token"]')?.value || 'Not found'}</div>
                        <div class="mt-2">
                            <a href="/api/run?command=${encodedCommand}" target="_blank" class="bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded text-xs">
                                Test GET
                            </a>
                            <button onclick="testPostCommand('${encodedCommand}')" class="bg-gray-600 hover:bg-gray-700 text-white px-2 py-1 rounded text-xs ml-2">
                                Test POST
                            </button>
                        </div>
                    `;
                    commandHistoryElement.appendChild(debugInfo);
                }
                
                // Create a fallback URL in case fetch fails
                const fallbackUrl = `/api/run?command=${encodedCommand}`;
                
                // Make AJAX request to execute command
                fetch(`/api/run?command=${encodedCommand}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Remove loading indicator
                        if (loadingOutput) {
                            loadingOutput.remove();
                        }
                        
                        // Create output element
                        const outputElement = document.createElement('div');
                        outputElement.className = 'command-output';
                        
                        // Add command output
                        let outputHtml = '';
                        
                        if (data.status === 'success') {
                            outputHtml += `<div class="success-output">${formatOutput(data.output)}</div>`;
                        } else {
                            outputHtml += `<div class="error-output">Error (Exit Code: ${data.exit_code})</div>`;
                            outputHtml += `<div class="error-output">${formatOutput(data.output)}</div>`;
                            
                            if (data.error_output) {
                                outputHtml += `<div class="error-output">${formatOutput(data.error_output)}</div>`;
                            }
                            
                            // Add suggestions for common errors
                            if (command.includes('artisan') && command.includes('optmize')) {
                                outputHtml += `<div class="suggestion">Did you mean: <span class="text-green-400">php artisan optimize</span>?</div>`;
                                outputHtml += `<div class="command-suggestions">
                                    <div class="suggestion-item" onclick="document.getElementById('command-input').value = 'php artisan optimize'; document.getElementById('command-input').focus();">php artisan optimize</div>
                                    <div class="suggestion-item" onclick="document.getElementById('command-input').value = 'php artisan optimize:clear'; document.getElementById('command-input').focus();">php artisan optimize:clear</div>
                                </div>`;
                            }
                        }
                        
                        outputElement.innerHTML = outputHtml;
                        commandHistoryElement.appendChild(outputElement);
                        
                        // Scroll to bottom
                        terminalBody.scrollTop = terminalBody.scrollHeight;
                    })
                    .catch(error => {
                        // Remove loading indicator
                        if (loadingOutput) {
                            loadingOutput.remove();
                        }
                        
                        console.error('Fetch error:', error);
                        
                        // Try POST fallback
                        const errorElement = document.createElement('div');
                        errorElement.className = 'command-output';
                        errorElement.innerHTML = `
                            <div class="error-output">Failed to execute command via GET: ${error.message}</div>
                            <div class="text-blue-400">Trying POST method...</div>
                        `;
                        commandHistoryElement.appendChild(errorElement);
                        
                        // Try POST method
                        fetch('/api/run', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: `command=${encodedCommand}&_token={{ csrf_token() }}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            const postOutputElement = document.createElement('div');
                            postOutputElement.className = 'command-output';
                            
                            if (data.status === 'success') {
                                postOutputElement.innerHTML = `<div class="success-output">${formatOutput(data.output)}</div>`;
                            } else {
                                postOutputElement.innerHTML = `<div class="error-output">Error (Exit Code: ${data.exit_code})</div>
                                                              <div class="error-output">${formatOutput(data.output)}</div>`;
                                
                                if (data.error_output) {
                                    postOutputElement.innerHTML += `<div class="error-output">${formatOutput(data.error_output)}</div>`;
                                }
                            }
                            
                            commandHistoryElement.appendChild(postOutputElement);
                            terminalBody.scrollTop = terminalBody.scrollHeight;
                        })
                        .catch(postError => {
                            // If POST also fails, show direct URL link
                            const postErrorElement = document.createElement('div');
                            postErrorElement.className = 'command-output';
                            postErrorElement.innerHTML = `
                                <div class="error-output">POST method also failed: ${postError.message}</div>
                                <div class="mt-2">
                                    <a href="${fallbackUrl}" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded text-sm">
                                        Try direct URL
                                    </a>
                                </div>
                            `;
                            commandHistoryElement.appendChild(postErrorElement);
                            terminalBody.scrollTop = terminalBody.scrollHeight;
                        });
                        
                        // Scroll to bottom
                        terminalBody.scrollTop = terminalBody.scrollHeight;
                    });
            }
            
            function formatOutput(output) {
                if (!output) return 'No output';
                
                // Escape HTML
                let escapedOutput = escapeHtml(output);
                
                // Add color to success/error messages
                escapedOutput = escapedOutput.replace(/success/gi, '<span class="text-green-400">success</span>');
                escapedOutput = escapedOutput.replace(/error/gi, '<span class="text-red-400">error</span>');
                escapedOutput = escapedOutput.replace(/warning/gi, '<span class="text-yellow-400">warning</span>');
                
                // Convert newlines to <br>
                return escapedOutput.replace(/\n/g, '<br>');
            }
            
            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
            
            // Focus input on load
            commandInput.focus();
            
            // Function to run a command directly
            window.runCommand = function(command) {
                commandInput.value = command;
                handleFormSubmit(new Event('submit'));
            };
            
            // Function to test POST command
            window.testPostCommand = function(encodedCommand) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/api/run';
                form.target = '_blank';
                
                const commandInput = document.createElement('input');
                commandInput.type = 'hidden';
                commandInput.name = 'command';
                commandInput.value = decodeURIComponent(encodedCommand);
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('input[name="_token"]').value;
                
                form.appendChild(commandInput);
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            };
            
            // Handle form submission
            window.handleFormSubmit = function(event) {
                event.preventDefault();
                const command = commandInput.value.trim();
                if (command) {
                    // Add to history
                    commandHistoryArray.push(command);
                    historyIndex = -1;
                    
                    // Handle built-in commands
                    if (command === 'clear') {
                        commandHistoryElement.innerHTML = '';
                        commandInput.value = '';
                        return false;
                    } else if (command === 'help') {
                        commandHelp.classList.remove('hidden');
                        addToHistory(command);
                        commandInput.value = '';
                        terminalBody.scrollTop = terminalBody.scrollHeight;
                        return false;
                    }
                    
                    // Add command to history display
                    addToHistory(command);
                    
                    // Execute the command via AJAX
                    executeCommand(command);
                    
                    // Clear input
                    commandInput.value = '';
                }
                return false;
            };
        });
    </script>
</body>
</html>