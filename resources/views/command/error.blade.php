<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Execution Error</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white shadow-lg rounded-lg overflow-hidden max-w-4xl mx-auto">
            <div class="bg-red-600 text-white px-6 py-4">
                <h1 class="text-xl font-bold">Command Execution Error</h1>
            </div>
            
            <div class="p-6">
                <div class="mb-6">
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                        <p class="font-bold">Error</p>
                        <p>{{ $error }}</p>
                    </div>
                </div>
                
                @if(isset($command))
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Command</h2>
                    <div class="bg-gray-100 p-3 rounded-md">
                        <code class="text-sm text-gray-800">{{ $command }}</code>
                    </div>
                </div>
                @endif
                
                @if(isset($allowed_commands))
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Allowed Commands</h2>
                    <div class="bg-gray-100 p-3 rounded-md">
                        <ul class="list-disc pl-5">
                            @foreach($allowed_commands as $cmd)
                                <li class="text-sm text-gray-800">{{ $cmd }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                
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
</body>
</html>