<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 API Status - Bakil Backend Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .status-pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .metric-card {
            transition: all 0.3s ease;
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">🔧 Bakil Backend Dashboard</h1>
            <p class="text-blue-100 text-lg">Real-time API Status........ & Performance Monitoring</p>
            <div class="mt-4">
                <a href="{{ url('/terminal') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-700 focus:ring focus:ring-blue-300 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Open Terminal
                </a>
            </div>
        </div>

        <!-- Main Status Card -->
        <div class="glass-effect rounded-2xl p-8 mb-8 text-center">
            <div class="flex items-center justify-center mb-4">
                <div id="mainStatus" class="w-4 h-4 rounded-full bg-green-500 status-pulse mr-3"></div>
                <h2 id="statusText" class="text-3xl font-bold text-white">API is Running</h2>
            </div>
            <p class="text-blue-100 text-lg mb-6">All systems operational</p>
            <div class="text-sm text-blue-200">
                Last updated: <span id="lastUpdated" class="font-mono">--</span>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="metric-card glass-effect rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-blue-100">Response Time</h3>
                    <span class="text-2xl">⚡</span>
                </div>
                <div id="responseTime" class="text-2xl font-bold text-white">-- ms</div>
                <div class="text-xs text-blue-200 mt-1">Average response</div>
            </div>

            <div class="metric-card glass-effect rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-blue-100">Uptime</h3>
                    <span class="text-2xl">📈</span>
                </div>
                <div id="uptime" class="text-2xl font-bold text-white">99.9%</div>
                <div class="text-xs text-blue-200 mt-1">Last 30 days</div>
            </div>

            <div class="metric-card glass-effect rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-blue-100">Requests/min</h3>
                    <span class="text-2xl">🔄</span>
                </div>
                <div id="requestsPerMin" class="text-2xl font-bold text-white">--</div>
                <div class="text-xs text-blue-200 mt-1">Current load</div>
            </div>

            <div class="metric-card glass-effect rounded-xl p-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-blue-100">Error Rate</h3>
                    <span class="text-2xl">🚨</span>
                </div>
                <div id="errorRate" class="text-2xl font-bold text-white">0.1%</div>
                <div class="text-xs text-green-200 mt-1">Low error rate</div>
            </div>
        </div>

        <!-- API Endpoints Status -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="glass-effect rounded-xl p-6">
                <h3 class="text-xl font-semibold text-white mb-4">🔗 API Endpoints</h3>
                <div id="endpointsList" class="space-y-3">
                    <!-- Endpoints will be populated here -->
                </div>
            </div>

            <div class="glass-effect rounded-xl p-6">
                <h3 class="text-xl font-semibold text-white mb-4">📊 Response Time Chart</h3>
                <canvas id="responseChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- System Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="glass-effect rounded-xl p-6">
                <h3 class="text-xl font-semibold text-white mb-4">💻 System Information</h3>
                <div class="space-y-3 text-blue-100">
                    <div class="flex justify-between">
                        <span>Framework:</span>
                        <span id="framework" class="font-mono text-white">Laravel 10.x</span>
                    </div>
                    <div class="flex justify-between">
                        <span>PHP Version:</span>
                        <span id="phpVersion" class="font-mono text-white">8.2.0</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Environment:</span>
                        <span id="environment" class="font-mono text-white">Production</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Server:</span>
                        <span id="server" class="font-mono text-white">Nginx/1.21.6</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Database:</span>
                        <span id="database" class="font-mono text-white">MySQL 8.0</span>
                    </div>
                </div>
            </div>

            <div class="glass-effect rounded-xl p-6">
                <h3 class="text-xl font-semibold text-white mb-4">🌐 Network Status</h3>
                <div id="networkStatus" class="space-y-3">
                    <!-- Network status will be populated here -->
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="glass-effect rounded-xl p-6">
            <h3 class="text-xl font-semibold text-white mb-4">📝 Recent Activity</h3>
            <div id="recentActivity" class="space-y-2 max-h-64 overflow-y-auto">
                <!-- Activity log will be populated here -->
            </div>
        </div>
    </div>

    <script>
        class APIStatusDashboard {
            constructor() {
                this.endpoints = [
                    { name: 'Authentication', url: '/api/auth/login', method: 'POST' },
                    { name: 'User Profile', url: '/api/user/profile', method: 'GET' },
                    { name: 'CSRF Cookie', url: '/sanctum/csrf-cookie', method: 'GET' },
                    { name: 'Health Check', url: '/api/health', method: 'GET' },
                    { name: 'Database', url: '/api/database/status', method: 'GET' }
                ];

                this.response = [];
                this.chart = null;
                this.init();
            }

            init() {
                this.updateTimestamp();
                this.initChart();
                this.startMonitoring();
                this.checkEndpoints();
                this.checkNetworkStatus();
                this.generateActivity();
                
                // Update every 30 seconds
                setInterval(() => {
                    this.updateTimestamp();
                    this.checkEndpoints();
                    this.updateMetrics();
                }, 30000);
                
                // Update chart every 5 seconds
                setInterval(() => {
                    this.updateChart();
                }, 5000);
            }

            updateTimestamp() {
                document.getElementById('lastUpdated').textContent = new Date().toLocaleString();
            }

            async checkEndpoints() {
                const endpointsList = document.getElementById('endpointsList');
                endpointsList.innerHTML = '';

                for (let endpoint of this.endpoints) {
                    const endpointEl = document.createElement('div');
                    endpointEl.className = 'flex items-center justify-between p-3 bg-white bg-opacity-10 rounded-lg';
                    
                    const startTime = Date.now();
                    const status = await this.pingEndpoint(endpoint);
                    const responseTime = Date.now() - startTime;
                    
                    const statusColor = status ? 'text-green-400' : 'text-red-400';
                    const statusIcon = status ? '✅' : '❌';
                    
                    endpointEl.innerHTML = `
                        <div class="flex items-center">
                            <span class="mr-2">${statusIcon}</span>
                            <div>
                                <div class="text-white font-medium">${endpoint.name}</div>
                                <div class="text-xs text-blue-200">${endpoint.method} ${endpoint.url}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="${statusColor} font-medium">${status ? 'Online' : 'Offline'}</div>
                            <div class="text-xs text-blue-200">${responseTime}ms</div>
                        </div>
                    `;
                    
                    endpointsList.appendChild(endpointEl);
                }
            }

            async pingEndpoint(endpoint) {
                try {
                    // Simulate API check - replace with actual endpoint calls
                    await new Promise(resolve => setTimeout(resolve, Math.random() * 200 + 50));
                    return Math.random() > 0.1; // 90% success rate simulation
                } catch (error) {
                    return false;
                }
            }

            checkNetworkStatus() {
                const networkStatus = document.getElementById('networkStatus');
                const services = [
                    { name: 'CDN', status: true, latency: 45 },
                    { name: 'Database', status: true, latency: 12 },
                    { name: 'Redis Cache', status: true, latency: 8 },
                    { name: 'Email Service', status: true, latency: 234 },
                    { name: 'File Storage', status: true, latency: 67 }
                ];

                networkStatus.innerHTML = services.map(service => {
                    const statusColor = service.status ? 'text-green-400' : 'text-red-400';
                    const statusIcon = service.status ? '🟢' : '🔴';
                    
                    return `
                        <div class="flex items-center justify-between p-2 bg-white bg-opacity-5 rounded">
                            <div class="flex items-center">
                                <span class="mr-2">${statusIcon}</span>
                                <span class="text-white">${service.name}</span>
                            </div>
                            <div class="text-right">
                                <span class="${statusColor} text-sm">${service.status ? 'Connected' : 'Disconnected'}</span>
                                <div class="text-xs text-blue-200">${service.latency}ms</div>
                            </div>
                        </div>
                    `;
                }).join('');
            }

            updateMetrics() {
                // Simulate real-time metrics
                const responseTime = Math.floor(Math.random() * 200) + 50;
                const requestsPerMin = Math.floor(Math.random() * 1000) + 500;
                
                document.getElementById('responseTime').textContent = responseTime + ' ms';
                document.getElementById('requestsPerMin').textContent = requestsPerMin.toLocaleString();
            }

            initChart() {
                const ctx = document.getElementById('responseChart').getContext('2d');
                this.chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: [{
                            label: 'Response Time (ms)',
                            data: [],
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                ticks: {
                                    color: 'rgba(255, 255, 255, 0.7)'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: 'rgba(255, 255, 255, 0.9)'
                                }
                            }
                        }
                    }
                });
            }

            updateChart() {
                const now = new Date();
                const timeLabel = now.toLocaleTimeString();
                const responseTime = Math.floor(Math.random() * 200) + 50;

                this.chart.data.labels.push(timeLabel);
                this.chart.data.datasets[0].data.push(responseTime);

                // Keep only last 10 data points
                if (this.chart.data.labels.length > 10) {
                    this.chart.data.labels.shift();
                    this.chart.data.datasets[0].data.shift();
                }

                this.chart.update();
            }

            generateActivity() {
                const activities = [
                    'User authentication successful',
                    'Database backup completed',
                    'Cache cleared and rebuilt',
                    'New user registration',
                    'API rate limit adjusted',
                    'Security scan completed',
                    'Performance optimization applied',
                    'SSL certificate renewed'
                ];

                const activityContainer = document.getElementById('recentActivity');
                
                // Generate initial activities
                for (let i = 0; i < 5; i++) {
                    this.addActivity(activities[Math.floor(Math.random() * activities.length)]);
                }

                // Add new activity every 10 seconds
                setInterval(() => {
                    this.addActivity(activities[Math.floor(Math.random() * activities.length)]);
                }, 10000);
            }

            addActivity(message) {
                const activityContainer = document.getElementById('recentActivity');
                const activityEl = document.createElement('div');
                activityEl.className = 'flex items-center justify-between p-2 bg-white bg-opacity-5 rounded text-sm';
                
                const timestamp = new Date().toLocaleTimeString();
                activityEl.innerHTML = `
                    <span class="text-blue-100">${message}</span>
                    <span class="text-blue-300 font-mono text-xs">${timestamp}</span>
                `;
                
                activityContainer.insertBefore(activityEl, activityContainer.firstChild);
                
                // Keep only last 20 activities
                while (activityContainer.children.length > 20) {
                    activityContainer.removeChild(activityContainer.lastChild);
                }
            }

            startMonitoring() {
                console.log('🚀 API Status Dashboard initialized');
                this.updateMetrics();
            }
        }

        // Initialize dashboard when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new APIStatusDashboard();
        });
    </script>
</body>
</html>