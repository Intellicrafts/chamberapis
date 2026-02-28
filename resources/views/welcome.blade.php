<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>⚡ Mera Vakil — API Control Center</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{
  --bg:#0a0d14;--surface:#111520;--surface2:#161b2c;--surface3:#1c2237;
  --border:#232a40;--border2:#2d3655;
  --accent:#4f8ef7;--accent2:#7c5cfc;--accent3:#00d4aa;
  --green:#22c55e;--yellow:#f59e0b;--red:#ef4444;--orange:#f97316;
  --text:#e2e8f0;--text2:#94a3b8;--text3:#64748b;
  --glow:0 0 20px rgba(79,142,247,.3);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;overflow-x:hidden}
::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:var(--surface)}
::-webkit-scrollbar-thumb{background:var(--border2);border-radius:10px}

/* Layout */
.layout{display:grid;grid-template-columns:220px 1fr;grid-template-rows:60px 1fr;min-height:100vh}
.topbar{grid-column:1/-1;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;padding:0 20px;gap:16px;z-index:100}
.sidebar{background:var(--surface);border-right:1px solid var(--border);padding:16px 0;position:sticky;top:60px;height:calc(100vh - 60px);overflow-y:auto;z-index:50}
.main{padding:24px;overflow-y:auto;max-height:calc(100vh - 60px)}

/* Topbar */
.logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:16px;letter-spacing:-.3px}
.logo-dot{width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));box-shadow:0 0 12px var(--accent);animation:pulse 2s infinite}
.top-chip{background:var(--surface2);border:1px solid var(--border);border-radius:20px;padding:4px 12px;font-size:12px;color:var(--text2);display:flex;align-items:center;gap:6px}
.top-chip .dot{width:7px;height:7px;border-radius:50%;background:var(--green);animation:pulse 1.5s infinite}
.top-actions{margin-left:auto;display:flex;gap:8px}
.btn{border:1px solid var(--border);background:var(--surface2);color:var(--text2);padding:7px 14px;border-radius:8px;font-size:12px;font-weight:500;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.2s}
.btn:hover{background:var(--surface3);color:var(--text);border-color:var(--border2)}
.btn-accent{background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;color:#fff}
.btn-accent:hover{opacity:.9;color:#fff}
.btn-danger{border-color:rgba(239,68,68,.4);color:var(--red)}
.btn-danger:hover{background:rgba(239,68,68,.1);color:var(--red)}
.btn-success{border-color:rgba(34,197,94,.4);color:var(--green)}
.btn-success:hover{background:rgba(34,197,94,.1);color:var(--green)}

/* Sidebar nav */
.nav-section{padding:8px 16px 4px;font-size:10px;font-weight:600;letter-spacing:1.5px;color:var(--text3);text-transform:uppercase}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:13px;font-weight:500;color:var(--text2);cursor:pointer;border-radius:0;transition:.15s;border-left:2px solid transparent}
.nav-item:hover{background:var(--surface2);color:var(--text)}
.nav-item.active{background:rgba(79,142,247,.1);color:var(--accent);border-left-color:var(--accent)}
.nav-icon{font-size:15px;width:18px;text-align:center}

/* Sections */
.section{display:none}
.section.active{display:block}

/* Cards */
.card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.card-title{font-size:14px;font-weight:600;color:var(--text)}
.card-subtitle{font-size:12px;color:var(--text3);margin-top:2px}

/* Stats grid */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;position:relative;overflow:hidden;transition:.25s}
.stat-card:hover{border-color:var(--border2);transform:translateY(-2px)}
.stat-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--accent2))}
.stat-label{font-size:12px;color:var(--text3);font-weight:500;text-transform:uppercase;letter-spacing:.8px}
.stat-value{font-size:28px;font-weight:800;color:var(--text);margin:6px 0 4px;font-family:'JetBrains Mono',monospace}
.stat-sub{font-size:12px;color:var(--text3)}
.stat-badge{position:absolute;top:16px;right:16px;font-size:20px;opacity:.6}
.stat-card.green::before{background:linear-gradient(90deg,var(--green),var(--accent3))}
.stat-card.yellow::before{background:linear-gradient(90deg,var(--yellow),var(--orange))}
.stat-card.red::before{background:linear-gradient(90deg,var(--red),var(--orange))}
.stat-card.purple::before{background:linear-gradient(90deg,var(--accent2),var(--accent))}

/* Health checks */
.health-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px;margin-bottom:24px}
.health-item{background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:14px;display:flex;align-items:center;gap:14px;transition:.2s}
.health-item:hover{border-color:var(--border2)}
.health-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.health-icon.ok{background:rgba(34,197,94,.15)}
.health-icon.error{background:rgba(239,68,68,.15)}
.health-icon.warning{background:rgba(245,158,11,.15)}
.health-icon.loading{background:rgba(79,142,247,.10)}
.health-label{font-size:13px;font-weight:600;color:var(--text)}
.health-msg{font-size:11px;color:var(--text3);margin-top:2px}
.health-latency{margin-left:auto;font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--accent3)}
.status-badge{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.status-badge.ok{background:rgba(34,197,94,.15);color:var(--green)}
.status-badge.error{background:rgba(239,68,68,.15);color:var(--red)}
.status-badge.warning{background:rgba(245,158,11,.15);color:var(--yellow)}

/* Logs */
.log-toolbar{display:flex;gap:8px;margin-bottom:14px;flex-wrap:wrap;align-items:center}
.log-filter{background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:6px 12px;border-radius:8px;font-size:12px;cursor:pointer}
.log-filter.active-filter{border-color:var(--accent);color:var(--accent)}
.log-search{flex:1;min-width:180px;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:7px 12px;border-radius:8px;font-size:12px;outline:none}
.log-search:focus{border-color:var(--accent)}
.log-container{background:#080c14;border:1px solid var(--border);border-radius:10px;height:420px;overflow-y:auto;padding:12px;font-family:'JetBrains Mono',monospace;font-size:12px}
.log-entry{padding:4px 0;border-bottom:1px solid rgba(255,255,255,.03);line-height:1.5;display:flex;gap:10px;min-width:0}
.log-entry:hover{background:rgba(255,255,255,.02)}
.log-time{color:var(--text3);flex-shrink:0;font-size:11px}
.log-level{flex-shrink:0;padding:1px 6px;border-radius:4px;font-size:10px;font-weight:700;text-transform:uppercase}
.log-level.emergency,.log-level.alert,.log-level.critical,.log-level.error{background:rgba(239,68,68,.2);color:var(--red)}
.log-level.warning{background:rgba(245,158,11,.2);color:var(--yellow)}
.log-level.notice,.log-level.info{background:rgba(79,142,247,.2);color:var(--accent)}
.log-level.debug{background:rgba(148,163,184,.15);color:var(--text3)}
.log-msg{color:var(--text2);word-break:break-all;min-width:0}
.log-empty{text-align:center;color:var(--text3);padding:40px}

/* Terminal */
.terminal-wrap{background:#060912;border:1px solid var(--border);border-radius:12px;overflow:hidden}
.terminal-topbar{background:#0d1117;padding:10px 14px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border)}
.t-dot{width:12px;height:12px;border-radius:50%}
.t-red{background:#ff5f57}.t-yellow{background:#ffbd2e}.t-green{background:#28c840}
.terminal-title-bar{margin-left:6px;font-size:12px;color:var(--text3);font-family:'JetBrains Mono',monospace}
.terminal-body{height:380px;overflow-y:auto;padding:14px;font-family:'JetBrains Mono',monospace;font-size:13px;line-height:1.6}
.t-line{margin-bottom:2px}
.t-prompt{color:#4f8ef7}
.t-success{color:#22c55e}
.t-error{color:#ef4444}
.t-info{color:#94a3b8}
.t-warn{color:#f59e0b}
.terminal-input-row{display:flex;align-items:center;padding:10px 14px;border-top:1px solid var(--border);background:#0d1117;gap:8px}
.t-prompt-label{color:var(--accent);font-family:'JetBrains Mono',monospace;font-size:13px;white-space:nowrap}
.t-input{flex:1;background:transparent;border:none;color:var(--text);font-family:'JetBrains Mono',monospace;font-size:13px;outline:none;caret-color:var(--accent)}
.t-run-btn{background:var(--accent);border:none;color:#fff;padding:6px 14px;border-radius:6px;font-size:12px;cursor:pointer;font-family:'JetBrains Mono',monospace}
.quick-cmds{display:flex;flex-wrap:wrap;gap:6px;padding:10px 14px;border-top:1px solid var(--border)}
.q-cmd{background:var(--surface2);border:1px solid var(--border);color:var(--text2);padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer;font-family:'JetBrains Mono',monospace;transition:.15s}
.q-cmd:hover{border-color:var(--accent);color:var(--accent)}

/* Tables */
.data-table{width:100%;border-collapse:collapse;font-size:13px}
.data-table th{text-align:left;padding:10px 12px;border-bottom:1px solid var(--border);color:var(--text3);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px}
.data-table td{padding:10px 12px;border-bottom:1px solid rgba(255,255,255,.04);color:var(--text2);font-family:'JetBrains Mono',monospace;font-size:12px}
.data-table tr:hover td{background:rgba(255,255,255,.02)}
.method-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:10px;font-weight:700}
.m-get{background:rgba(34,197,94,.15);color:#22c55e}
.m-post{background:rgba(79,142,247,.15);color:#4f8ef7}
.m-put,.m-patch{background:rgba(245,158,11,.15);color:#f59e0b}
.m-delete{background:rgba(239,68,68,.15);color:#ef4444}

/* Misc */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.section-title{font-size:20px;font-weight:700;color:var(--text)}
.section-sub{font-size:13px;color:var(--text3);margin-top:3px}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}
.chart-wrap{height:200px;position:relative}
.tag{background:var(--surface2);border:1px solid var(--border);border-radius:5px;padding:2px 8px;font-size:11px;font-family:'JetBrains Mono',monospace;color:var(--text3)}
.kv-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.04)}
.kv-row:last-child{border-bottom:none}
.kv-key{font-size:12px;color:var(--text3)}
.kv-val{font-size:12px;font-family:'JetBrains Mono',monospace;color:var(--text)}
.spinner{width:16px;height:16px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .7s linear infinite;display:inline-block}
.progress-bar{width:100%;height:6px;background:var(--surface3);border-radius:10px;overflow:hidden;margin-top:6px}
.progress-fill{height:100%;border-radius:10px;background:linear-gradient(90deg,var(--accent),var(--accent2));transition:.5s}
.alert-banner{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.25);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:12px;margin-bottom:20px}
.alert-banner.degraded{background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.25)}
.alert-banner.warning{background:rgba(245,158,11,.08);border-color:rgba(245,158,11,.25)}
.routes-filter{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
.routes-container{max-height:500px;overflow-y:auto}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
.fade-in{animation:fadeIn .3s ease}
.toast{position:fixed;bottom:20px;right:20px;background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:12px 18px;font-size:13px;z-index:9999;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,.4);opacity:0;transition:.3s;pointer-events:none}
.toast.show{opacity:1}
.toast.success{border-color:rgba(34,197,94,.4);color:var(--green)}
.toast.error{border-color:rgba(239,68,68,.4);color:var(--red)}
@media(max-width:900px){.layout{grid-template-columns:1fr}.sidebar{display:none}.main{padding:14px}.grid-2,.grid-3{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="layout">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="logo">
      <div class="logo-dot"></div>
      Mera Vakil API
    </div>
    <div class="top-chip"><div class="dot"></div><span id="topStatus">Checking…</span></div>
    <div class="top-chip">🕐 <span id="topTime">--:--:--</span></div>
    <div class="top-chip">⚡ <span id="topLatency">-- ms</span></div>
    <div class="top-actions">
      <button class="btn btn-success" onclick="runOptimize()">⚡ Optimize</button>
      <button class="btn btn-danger" onclick="clearAllCache()">🗑 Clear Cache</button>
      <button class="btn btn-accent" onclick="refreshAll()">↻ Refresh</button>
    </div>
  </div>

  <!-- SIDEBAR -->
  <nav class="sidebar">
    <div class="nav-section">Monitor</div>
    <div class="nav-item active" onclick="showSection('overview')" id="nav-overview">
      <span class="nav-icon">🏠</span> Overview
    </div>
    <div class="nav-item" onclick="showSection('logs')" id="nav-logs">
      <span class="nav-icon">📋</span> Live Logs
    </div>
    <div class="nav-item" onclick="showSection('metrics')" id="nav-metrics">
      <span class="nav-icon">📊</span> Metrics
    </div>
    <div class="nav-section">Tools</div>
    <div class="nav-item" onclick="showSection('terminal')" id="nav-terminal">
      <span class="nav-icon">⌨️</span> Terminal
    </div>
    <div class="nav-item" onclick="showSection('routes')" id="nav-routes">
      <span class="nav-icon">🔗</span> API Routes
    </div>
    <div class="nav-item" onclick="showSection('database')" id="nav-database">
      <span class="nav-icon">🗄️</span> Database
    </div>
    <div class="nav-section">System</div>
    <div class="nav-item" onclick="showSection('controls')" id="nav-controls">
      <span class="nav-icon">🎛️</span> Controls
    </div>
  </nav>

  <!-- MAIN CONTENT -->
  <main class="main">

    <!-- ═══════════════════════════ OVERVIEW ═══════════════════════════ -->
    <div class="section active fade-in" id="section-overview">
      <div class="section-header">
        <div>
          <div class="section-title">System Overview</div>
          <div class="section-sub">Real-time health monitoring — <span id="lastRefresh">--</span></div>
        </div>
      </div>

      <div id="alertBanner" class="alert-banner">
        <span id="alertIcon">✅</span>
        <div>
          <div style="font-weight:700;font-size:14px" id="alertTitle">All Systems Operational</div>
          <div style="font-size:12px;color:var(--text3)" id="alertSub">All API health checks passing</div>
        </div>
        <div style="margin-left:auto;font-size:12px;color:var(--text3)">Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}</div>
      </div>

      <div class="stats-grid">
        <div class="stat-card green">
          <div class="stat-badge">⚡</div>
          <div class="stat-label">Response Time</div>
          <div class="stat-value" id="s-resp">--</div>
          <div class="stat-sub">milliseconds</div>
        </div>
        <div class="stat-card purple">
          <div class="stat-badge">🔗</div>
          <div class="stat-label">API Routes</div>
          <div class="stat-value" id="s-routes">--</div>
          <div class="stat-sub">registered endpoints</div>
        </div>
        <div class="stat-card yellow">
          <div class="stat-badge">💾</div>
          <div class="stat-label">Memory Used</div>
          <div class="stat-value" id="s-mem">--</div>
          <div class="stat-sub">MB current usage</div>
        </div>
        <div class="stat-card red">
          <div class="stat-badge">🗄️</div>
          <div class="stat-label">DB Tables</div>
          <div class="stat-value" id="s-tables">--</div>
          <div class="stat-sub">total tables</div>
        </div>
      </div>

      <!-- Health Checks -->
      <div class="card" style="margin-bottom:20px">
        <div class="card-header">
          <div>
            <div class="card-title">🩺 Health Checks</div>
            <div class="card-subtitle">Live service status</div>
          </div>
          <button class="btn" onclick="loadHealth()">↻ Check</button>
        </div>
        <div class="health-grid" id="healthGrid">
          <div class="health-item"><div class="health-icon loading">⏳</div><div><div class="health-label">Loading…</div></div></div>
        </div>
      </div>

      <!-- Charts row -->
      <div class="grid-2">
        <div class="card">
          <div class="card-header">
            <div><div class="card-title">📈 Response Time</div><div class="card-subtitle">Live ms — updates every 5s</div></div>
          </div>
          <div class="chart-wrap"><canvas id="respChart"></canvas></div>
        </div>
        <div class="card">
          <div class="card-header">
            <div><div class="card-title">💻 System Info</div></div>
          </div>
          <div id="sysInfoKV"></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════ LIVE LOGS ═══════════════════════════ -->
    <div class="section fade-in" id="section-logs">
      <div class="section-header">
        <div>
          <div class="section-title">📋 Live Logs</div>
          <div class="section-sub" id="logMeta">Loading log file…</div>
        </div>
        <div style="display:flex;gap:8px">
          <button class="btn" id="autoRefreshBtn" onclick="toggleAutoLog()">▶ Auto</button>
          <button class="btn" onclick="loadLogs()">↻ Refresh</button>
          <button class="btn btn-danger" onclick="clearLogView()">🗑 Clear View</button>
        </div>
      </div>
      <div class="log-toolbar">
        <button class="log-filter active-filter" onclick="setLogLevel('all',this)">All</button>
        <button class="log-filter" onclick="setLogLevel('error',this)">Error</button>
        <button class="log-filter" onclick="setLogLevel('warning',this)">Warning</button>
        <button class="log-filter" onclick="setLogLevel('info',this)">Info</button>
        <button class="log-filter" onclick="setLogLevel('debug',this)">Debug</button>
        <input class="log-search" id="logSearch" placeholder="🔍  Search logs…" oninput="loadLogs()">
        <select class="log-search" id="logLines" onchange="loadLogs()" style="max-width:100px">
          <option value="50">50 lines</option>
          <option value="100" selected>100 lines</option>
          <option value="200">200 lines</option>
          <option value="500">500 lines</option>
        </select>
      </div>
      <div class="log-container" id="logContainer">
        <div class="log-empty">Loading logs…</div>
      </div>
    </div>

    <!-- ═══════════════════════════ METRICS ═══════════════════════════ -->
    <div class="section fade-in" id="section-metrics">
      <div class="section-header">
        <div><div class="section-title">📊 System Metrics</div><div class="section-sub">Deep performance details</div></div>
        <button class="btn" onclick="loadMetrics()">↻ Refresh</button>
      </div>
      <div class="grid-3" style="margin-bottom:16px" id="metricsCards">
        <div class="card"><div class="stat-label">Memory Used</div><div class="stat-value" id="m-mem">--</div><div class="progress-bar"><div class="progress-fill" id="m-membar" style="width:0%"></div></div></div>
        <div class="card"><div class="stat-label">Disk Usage</div><div class="stat-value" id="m-disk">--</div><div class="progress-bar"><div class="progress-fill" id="m-diskbar" style="width:0%;background:linear-gradient(90deg,var(--yellow),var(--orange))"></div></div></div>
        <div class="card"><div class="stat-label">Peak Memory</div><div class="stat-value" id="m-peak">--</div><div class="stat-sub" id="m-memlimit"></div></div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><div class="card-title">🖥 Application</div></div>
          <div id="appMetrics"></div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">🐘 PHP Runtime</div></div>
          <div id="phpMetrics"></div>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════ TERMINAL ═══════════════════════════ -->
    <div class="section fade-in" id="section-terminal">
      <div class="section-header">
        <div><div class="section-title">⌨️ Terminal</div><div class="section-sub">Execute artisan &amp; shell commands</div></div>
      </div>
      <div class="terminal-wrap">
        <div class="terminal-topbar">
          <div class="t-dot t-red"></div>
          <div class="t-dot t-yellow"></div>
          <div class="t-dot t-green"></div>
          <div class="terminal-title-bar">bakil@{{ gethostname() }}:~/api — {{ strtoupper(app()->environment()) }}</div>
          <button class="btn" style="margin-left:auto;font-size:11px;padding:4px 10px" onclick="clearTerm()">Clear</button>
        </div>
        <div class="terminal-body" id="termBody">
          <div class="t-line t-info">╔══════════════════════════════════════════════╗</div>
          <div class="t-line t-info">║    Mera Vakil API Control Terminal v1.0      ║</div>
          <div class="t-line t-info">╚══════════════════════════════════════════════╝</div>
          <div class="t-line t-success">✓ Connected to {{ app()->environment() }} environment</div>
          <div class="t-line t-success">✓ Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}</div>
          <div class="t-line" style="color:var(--text3)">Type a command below. All artisan commands supported.</div>
          <div class="t-line"> </div>
        </div>
        <div class="quick-cmds">
          <span style="font-size:11px;color:var(--text3);align-self:center">Quick:</span>
          @php
            $qcmds = ['php artisan optimize','php artisan cache:clear','php artisan config:cache','php artisan route:cache','php artisan migrate:status','php artisan queue:restart','php artisan --version','php artisan list'];
          @endphp
          @foreach($qcmds as $c)
          <button class="q-cmd" onclick="setTermCmd('{{ $c }}')">{{ $c }}</button>
          @endforeach
        </div>
        <div class="terminal-input-row">
          <span class="t-prompt-label">{{ app()->environment() }}@api $</span>
          <input class="t-input" id="termInput" placeholder="php artisan …" autocomplete="off"
            onkeydown="if(event.key==='Enter')runTermCmd();if(event.key==='ArrowUp')termHistory(-1);if(event.key==='ArrowDown')termHistory(1);">
          <button class="t-run-btn" onclick="runTermCmd()">▶ Run</button>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════ ROUTES ═══════════════════════════ -->
    <div class="section fade-in" id="section-routes">
      <div class="section-header">
        <div><div class="section-title">🔗 API Routes</div><div class="section-sub" id="routeMeta">Loading…</div></div>
        <button class="btn" onclick="loadRoutes()">↻ Refresh</button>
      </div>
      <div class="routes-filter" id="routeMethodFilter">
        <button class="log-filter active-filter" onclick="filterRoutes('ALL',this)">All</button>
        <button class="log-filter" onclick="filterRoutes('GET',this)">GET</button>
        <button class="log-filter" onclick="filterRoutes('POST',this)">POST</button>
        <button class="log-filter" onclick="filterRoutes('PUT',this)">PUT/PATCH</button>
        <button class="log-filter" onclick="filterRoutes('DELETE',this)">DELETE</button>
      </div>
      <input class="log-search" id="routeSearch" placeholder="🔍  Filter routes…" oninput="renderRoutes()" style="margin-bottom:12px;width:100%">
      <div class="card">
        <div class="routes-container">
          <table class="data-table"><thead><tr>
            <th>Method</th><th>URI</th><th>Name</th><th>Middleware</th><th>Action</th>
          </tr></thead>
          <tbody id="routesBody"><tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text3)">Loading routes…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════ DATABASE ═══════════════════════════ -->
    <div class="section fade-in" id="section-database">
      <div class="section-header">
        <div><div class="section-title">🗄️ Database</div><div class="section-sub" id="dbMeta">Loading…</div></div>
        <button class="btn" onclick="loadDB()">↻ Refresh</button>
      </div>
      <div class="card">
        <div class="routes-container">
          <table class="data-table"><thead><tr>
            <th>Table</th><th>Rows</th><th>Data MB</th><th>Index MB</th><th>Total MB</th><th>Engine</th>
          </tr></thead>
          <tbody id="dbBody"><tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text3)">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══════════════════════════ CONTROLS ═══════════════════════════ -->
    <div class="section fade-in" id="section-controls">
      <div class="section-header">
        <div><div class="section-title">🎛️ Controls</div><div class="section-sub">Manage application caches &amp; rebuild</div></div>
      </div>
      <div class="grid-2">
        <div class="card">
          <div class="card-header"><div class="card-title">🗑 Cache Management</div></div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <button class="btn" style="justify-content:flex-start;padding:12px 16px" onclick="clearCache('config')">⚙️ &nbsp;Clear Config Cache</button>
            <button class="btn" style="justify-content:flex-start;padding:12px 16px" onclick="clearCache('route')">🔗 &nbsp;Clear Route Cache</button>
            <button class="btn" style="justify-content:flex-start;padding:12px 16px" onclick="clearCache('view')">🖼 &nbsp;Clear View Cache</button>
            <button class="btn" style="justify-content:flex-start;padding:12px 16px" onclick="clearCache('cache')">💾 &nbsp;Clear Application Cache</button>
            <button class="btn btn-danger" style="justify-content:flex-start;padding:12px 16px" onclick="clearAllCache()">🗑 &nbsp;Clear All Caches</button>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="card-title">⚡ Optimization</div></div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <button class="btn btn-accent" style="justify-content:flex-start;padding:12px 16px" onclick="runOptimize()">⚡ &nbsp;Run php artisan optimize</button>
            <button class="btn btn-success" style="justify-content:flex-start;padding:12px 16px" onclick="runArtisan('config:cache')">⚙️ &nbsp;Cache Config</button>
            <button class="btn btn-success" style="justify-content:flex-start;padding:12px 16px" onclick="runArtisan('route:cache')">🔗 &nbsp;Cache Routes</button>
            <button class="btn btn-success" style="justify-content:flex-start;padding:12px 16px" onclick="runArtisan('view:cache')">🖼 &nbsp;Cache Views</button>
          </div>
          <div id="controlOutput" style="margin-top:14px;background:#060912;border-radius:8px;padding:12px;font-family:'JetBrains Mono',monospace;font-size:12px;color:var(--text2);display:none;max-height:200px;overflow-y:auto"></div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let currentLogLevel = 'all';
let allRoutes = [];
let activeRouteMethod = 'ALL';
let termCmds = [], termCmdIdx = -1;
let autoLogTimer = null, autoLogOn = false;
let respChart = null, respData = {labels:[], data:[]};

// ─── Navigation ───────────────────────────────────────────────────────────────
function showSection(id) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('section-' + id).classList.add('active','fade-in');
  document.getElementById('nav-' + id).classList.add('active');
  if (id === 'logs') loadLogs();
  if (id === 'metrics') loadMetrics();
  if (id === 'routes') loadRoutes();
  if (id === 'database') loadDB();
}

// ─── Clock ────────────────────────────────────────────────────────────────────
function tick() {
  document.getElementById('topTime').textContent = new Date().toLocaleTimeString();
}
setInterval(tick, 1000); tick();

// ─── Toast ────────────────────────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
  t.className = 'toast show ' + type;
  setTimeout(() => t.className = 'toast', 3000);
}

// ─── Health ───────────────────────────────────────────────────────────────────
async function loadHealth() {
  try {
    const res = await fetch('/dashboard/health');
    const d = await res.json();
    document.getElementById('topLatency').textContent = d.response_ms + ' ms';
    document.getElementById('s-resp').textContent = d.response_ms;
    document.getElementById('lastRefresh').textContent = new Date().toLocaleTimeString();

    // Alert banner
    const banner = document.getElementById('alertBanner');
    banner.className = 'alert-banner' + (d.status === 'degraded' ? ' degraded' : d.status === 'warning' ? ' warning' : '');
    document.getElementById('alertIcon').textContent = d.status === 'healthy' ? '✅' : d.status === 'warning' ? '⚠️' : '🚨';
    document.getElementById('alertTitle').textContent = d.status === 'healthy' ? 'All Systems Operational' : d.status === 'warning' ? 'System Warning' : 'System Degraded';
    document.getElementById('alertSub').textContent = `Status: ${d.status} · Env: ${d.environment}`;
    document.getElementById('topStatus').textContent = d.status === 'healthy' ? 'All Systems OK' : d.status.toUpperCase();

    // Health grid
    const icons = { database:'🗄️', cache:'⚡', queue:'📬', storage:'💾', mail:'📧' };
    const grid = document.getElementById('healthGrid');
    grid.innerHTML = '';
    for (const [key, val] of Object.entries(d.checks)) {
      const el = document.createElement('div');
      el.className = 'health-item fade-in';
      el.innerHTML = `
        <div class="health-icon ${val.status}">${icons[key] || '🔧'}</div>
        <div style="min-width:0">
          <div class="health-label">${key.charAt(0).toUpperCase()+key.slice(1)}</div>
          <div class="health-msg">${val.message || ''}</div>
          <div style="margin-top:4px"><span class="status-badge ${val.status}">${val.status.toUpperCase()}</span></div>
        </div>
        ${val.latency ? `<div class="health-latency">${val.latency}ms</div>` : ''}`;
      grid.appendChild(el);
    }

    // Add response time to chart
    const now = new Date().toLocaleTimeString();
    respData.labels.push(now);
    respData.data.push(d.response_ms);
    if (respData.labels.length > 20) { respData.labels.shift(); respData.data.shift(); }
    if (respChart) { respChart.data.labels = respData.labels; respChart.data.datasets[0].data = respData.data; respChart.update('none'); }
  } catch(e) {
    document.getElementById('topStatus').textContent = 'Error';
  }
}

// ─── Metrics ──────────────────────────────────────────────────────────────────
async function loadMetrics() {
  const [mRes, dbRes] = await Promise.all([fetch('/dashboard/metrics'), fetch('/dashboard/db-stats')]);
  const m = await mRes.json();
  const db = await dbRes.json();

  document.getElementById('m-mem').textContent = m.memory.used_mb + ' MB';
  document.getElementById('m-peak').textContent = m.memory.peak_mb + ' MB';
  document.getElementById('m-memlimit').textContent = 'Limit: ' + m.memory.limit;
  document.getElementById('m-disk').textContent = m.disk.used_pct + '%';
  document.getElementById('m-membar').style.width = Math.min(m.memory.used_mb / parseInt(m.memory.limit) * 100, 100) + '%';
  document.getElementById('m-diskbar').style.width = m.disk.used_pct + '%';
  document.getElementById('s-mem').textContent = m.memory.used_mb;
  document.getElementById('s-routes').textContent = m.app.route_count;
  document.getElementById('s-tables').textContent = db.count ?? '--';

  const kv = (k,v) => `<div class="kv-row"><span class="kv-key">${k}</span><span class="kv-val">${v}</span></div>`;
  document.getElementById('appMetrics').innerHTML =
    kv('Laravel Version', m.app.laravel_version) +
    kv('Environment', m.app.environment) +
    kv('Debug Mode', m.app.debug ? '🔴 ON' : '🟢 OFF') +
    kv('Route Count', m.app.route_count) +
    kv('DB Driver', m.database.driver) +
    kv('Database', m.database.database) +
    kv('DB Host', m.database.host);

  document.getElementById('phpMetrics').innerHTML =
    kv('PHP Version', m.php.version) +
    kv('Max Exec Time', m.php.max_exec_sec + 's') +
    kv('Memory Limit', m.memory.limit) +
    kv('Disk Free', m.disk.free_gb + ' GB') +
    kv('Disk Total', m.disk.total_gb + ' GB') +
    kv('Extensions', m.php.extensions.length + ' loaded');

  document.getElementById('sysInfoKV').innerHTML =
    kv('Framework', 'Laravel ' + m.app.laravel_version) +
    kv('PHP', m.php.version) +
    kv('Environment', m.app.environment) +
    kv('Debug', m.app.debug ? 'ON 🔴' : 'OFF 🟢') +
    kv('Routes', m.app.route_count) +
    kv('Memory', m.memory.used_mb + ' / ' + m.memory.limit);
}

// ─── Logs ─────────────────────────────────────────────────────────────────────
async function loadLogs() {
  const lvl = currentLogLevel;
  const search = document.getElementById('logSearch').value;
  const lines = document.getElementById('logLines').value;
  const res = await fetch(`/dashboard/logs?level=${lvl}&search=${encodeURIComponent(search)}&lines=${lines}`);
  const d = await res.json();

  document.getElementById('logMeta').textContent = `${d.log_file} · ${d.total_lines} entries · ${(d.total_bytes/1024).toFixed(1)} KB`;

  const container = document.getElementById('logContainer');
  if (!d.entries || !d.entries.length) {
    container.innerHTML = '<div class="log-empty">📭 No log entries found</div>';
    return;
  }

  container.innerHTML = d.entries.map(e => `
    <div class="log-entry">
      <span class="log-time">${e.datetime}</span>
      <span class="log-level ${e.level}">${e.level}</span>
      <span class="log-msg">${escHtml(e.message)}</span>
    </div>`).join('');
}

function setLogLevel(lvl, btn) {
  currentLogLevel = lvl;
  document.querySelectorAll('.log-filter').forEach(b => b.classList.remove('active-filter'));
  btn.classList.add('active-filter');
  loadLogs();
}

function clearLogView() {
  document.getElementById('logContainer').innerHTML = '<div class="log-empty">📭 Log view cleared</div>';
}

function toggleAutoLog() {
  autoLogOn = !autoLogOn;
  const btn = document.getElementById('autoRefreshBtn');
  if (autoLogOn) {
    btn.textContent = '⏸ Auto'; btn.style.color = 'var(--green)';
    autoLogTimer = setInterval(loadLogs, 5000);
    loadLogs();
  } else {
    btn.textContent = '▶ Auto'; btn.style.color = '';
    clearInterval(autoLogTimer);
  }
}

// ─── Routes ───────────────────────────────────────────────────────────────────
async function loadRoutes() {
  const res = await fetch('/dashboard/routes');
  const d = await res.json();
  allRoutes = d.routes;
  document.getElementById('routeMeta').textContent = `${d.total} total routes`;
  renderRoutes();
}

function filterRoutes(method, btn) {
  activeRouteMethod = method;
  document.querySelectorAll('#routeMethodFilter .log-filter').forEach(b => b.classList.remove('active-filter'));
  btn.classList.add('active-filter');
  renderRoutes();
}

function renderRoutes() {
  const search = document.getElementById('routeSearch').value.toLowerCase();
  let routes = allRoutes;
  if (activeRouteMethod !== 'ALL') {
    routes = routes.filter(r => {
      if (activeRouteMethod === 'PUT/PATCH') return r.method === 'PUT' || r.method === 'PATCH';
      return r.method === activeRouteMethod;
    });
  }
  if (search) routes = routes.filter(r => r.uri.toLowerCase().includes(search) || r.name.toLowerCase().includes(search) || r.action.toLowerCase().includes(search));

  const tbody = document.getElementById('routesBody');
  tbody.innerHTML = routes.map(r => `
    <tr>
      <td><span class="method-badge m-${r.method.toLowerCase()}">${r.method}</span></td>
      <td style="color:var(--text)">${escHtml(r.uri)}</td>
      <td>${r.name ? `<span class="tag">${escHtml(r.name)}</span>` : '<span style="color:var(--text3)">-</span>'}</td>
      <td><span style="color:var(--text3);font-size:11px">${escHtml(r.middleware || '-')}</span></td>
      <td style="color:var(--text3);font-size:11px;max-width:220px;overflow:hidden;text-overflow:ellipsis">${escHtml(r.action)}</td>
    </tr>`).join('') || '<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text3)">No routes match</td></tr>';
}

// ─── Database ─────────────────────────────────────────────────────────────────
async function loadDB() {
  const res = await fetch('/dashboard/db-stats');
  const d = await res.json();
  document.getElementById('dbMeta').textContent = `${d.database} @ ${d.host} · ${d.count} tables · driver: ${d.connection}`;
  document.getElementById('dbBody').innerHTML = d.tables.map(t => `
    <tr>
      <td style="color:var(--text)">${escHtml(t.name)}</td>
      <td>${Number(t.rows).toLocaleString()}</td>
      <td>${t.data_mb}</td>
      <td>${t.index_mb}</td>
      <td style="color:var(--accent3)">${t.total_mb}</td>
      <td><span class="tag">${escHtml(t.engine)}</span></td>
    </tr>`).join('') || '<tr><td colspan="6" style="padding:30px;text-align:center;color:var(--text3)">No tables found</td></tr>';
}

// ─── Terminal ─────────────────────────────────────────────────────────────────
async function runTermCmd() {
  const input = document.getElementById('termInput');
  const cmd = input.value.trim();
  if (!cmd) return;
  termCmds.push(cmd); termCmdIdx = termCmds.length;
  input.value = '';
  appendTerm(`$ ${cmd}`, 't-prompt');
  appendTerm('Executing…', 't-info');
  const res = await fetch('/dashboard/run', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ command: cmd })
  });
  const d = await res.json();
  // Remove "Executing…"
  const tb = document.getElementById('termBody');
  tb.removeChild(tb.lastElementChild);
  if (d.output) appendTermRaw(d.output, d.status === 'success' ? 't-success' : 't-error');
  if (d.error_output) appendTermRaw(d.error_output, 't-warn');
  appendTerm('', '');
}

function appendTerm(txt, cls) {
  const tb = document.getElementById('termBody');
  const d = document.createElement('div');
  d.className = 't-line ' + cls;
  d.textContent = txt;
  tb.appendChild(d);
  tb.scrollTop = tb.scrollHeight;
}

function appendTermRaw(txt, cls) {
  txt.split('\n').forEach(line => appendTerm(line, cls));
}

function setTermCmd(cmd) {
  document.getElementById('termInput').value = cmd;
  document.getElementById('termInput').focus();
}

function clearTerm() {
  const tb = document.getElementById('termBody');
  tb.innerHTML = '<div class="t-line t-info">Terminal cleared.</div>';
}

function termHistory(dir) {
  termCmdIdx = Math.max(0, Math.min(termCmds.length - 1, termCmdIdx + dir));
  document.getElementById('termInput').value = termCmds[termCmdIdx] || '';
}

// ─── Controls ─────────────────────────────────────────────────────────────────
async function clearCache(type) {
  const res = await fetch('/dashboard/cache/clear', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ type })
  });
  const d = await res.json();
  toast('Cache cleared: ' + type, d.status === 'success' ? 'success' : 'error');
  showControlOutput('✅ Cleared: ' + JSON.stringify(d.cleared));
}

async function clearAllCache() {
  await clearCache('all');
}

async function runOptimize() {
  toast('Running optimize…', 'success');
  const res = await fetch('/dashboard/optimize', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
  });
  const d = await res.json();
  toast(d.status === 'success' ? 'Optimize complete' : 'Optimize failed', d.status === 'success' ? 'success' : 'error');
  showControlOutput(d.output || d.message || '');
}

async function runArtisan(cmd) {
  const res = await fetch('/dashboard/run', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    body: JSON.stringify({ command: 'php artisan ' + cmd })
  });
  const d = await res.json();
  toast(cmd + (d.status === 'success' ? ' done' : ' failed'), d.status);
  showControlOutput(d.output || '');
}

function showControlOutput(text) {
  const el = document.getElementById('controlOutput');
  el.style.display = 'block';
  el.textContent = text;
}

function refreshAll() {
  loadHealth();
  loadMetrics();
  toast('Dashboard refreshed', 'success');
}

// ─── Chart ────────────────────────────────────────────────────────────────────
function initChart() {
  const ctx = document.getElementById('respChart').getContext('2d');
  respChart = new Chart(ctx, {
    type: 'line',
    data: { labels: [], datasets: [{ label: 'Response ms', data: [], borderColor: '#4f8ef7', backgroundColor: 'rgba(79,142,247,.1)', fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: '#4f8ef7' }] },
    options: {
      responsive: true, maintainAspectRatio: false, animation: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,.05)' } },
        y: { beginAtZero: true, ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,.05)' } }
      }
    }
  });
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ─── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initChart();
  loadHealth();
  loadMetrics();
  // Auto-refresh health every 30s
  setInterval(loadHealth, 30000);
});
</script>
</body>
</html>