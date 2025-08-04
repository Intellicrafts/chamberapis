<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Execution Result</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .terminal {
            background-color: #1e1e1e;
            color: #f0f0f0;
            font-family: 'Courier New', monospace;
            border-radius: 6px;
            padding: 16px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .command {
            color: #64b5f6;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #444;
            padding-bottom: 8px;
        }
        .success {
            color: #81c784;
        }
        .error {
            color: #e57373;
        }
        .output-line {
            line-height: 1.5;
        }
        .copy-btn {
            transition: all 0.3s ease;
        }
        .copy-btn:hover {
            background-color: #4a5568;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden max-w-4xl mx-auto">
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold">Command Execution Result</h1>
                <div class="flex space-x-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ ucfirst($status) }}
                    </span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                        Exit Code: {{ $exit_code }}
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Command</h2>
                    <div class="bg-gray-100 p-3 rounded-md flex justify-between items-center">
                        <code class="text-sm text-gray-800">{{ $command }}</code>
                        <button onclick="copyToClipboard('{{ $command }}')" class="copy-btn bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1 rounded text-sm">
                            Copy
                        </button>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Output</h2>
                    <div class="terminal relative">
                        <div class="command">$ {{ $command }}</div>
                        <div class="output {{ $status === 'success' ? 'success' : 'error' }}">
                            @if(empty($output) && empty($error_output))
                                <span class="text-gray-400">No output</span>
                            @else
                                @foreach(explode("\n", $output) as $line)
                                    <div class="output-line">{{ $line }}</div>
                                @endforeach
                                
                                @if(!empty($error_output))
                                    <div class="mt-4 text-red-400">Error Output:</div>
                                    @foreach(explode("\n", $error_output) as $line)
                                        <div class="output-line text-red-400">{{ $line }}</div>
                                    @endforeach
                                @endif
                            @endif
                        </div>
                        <button onclick="copyToClipboard(document.querySelector('.output').innerText)" class="copy-btn absolute top-4 right-4 bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                            Copy Output
                        </button>
                    </div>
                </div>
                
                <div class="flex justify-between items-center mt-8">
                    <a href="javascript:history.back()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded">
                        Back
                    </a>
                    <a href="{{ url('/') }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        Home
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Show a temporary notification
            const notification = document.createElement('div');
            notification.textContent = 'Copied to clipboard!';
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.padding = '10px 20px';
            notification.style.backgroundColor = '#4CAF50';
            notification.style.color = 'white';
            notification.style.borderRadius = '4px';
            notification.style.zIndex = '1000';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 500);
            }, 2000);
        }
    </script>
</body>
</html>