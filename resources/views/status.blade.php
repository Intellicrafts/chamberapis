<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🔧 API Status - Bakil Backend</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .card {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .api-status {
            font-size: 1.2rem;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">
    <div class="card text-center p-5">
        <h1 class="mb-3 text-success">✅ API is Running</h1>
        <p class="api-status text-muted">Welcome to the <strong>BakilApp Backend</strong></p>
        <hr>
        <ul class="list-unstyled">
            <li><strong>Framework:</strong> Laravel {{ Illuminate\Foundation\Application::VERSION }}</li>
            <li><strong>PHP Version:</strong> {{ PHP_VERSION }}</li>
            <li><strong>Environment:</strong> {{ app()->environment() }}</li>
            <li><strong>Time:</strong> {{ now() }}</li>
        </ul>
        <a href="/sanctum/csrf-cookie" class="btn btn-outline-primary mt-3">Test CSRF Cookie Endpoint</a>
    </div>
</body>
</html>
