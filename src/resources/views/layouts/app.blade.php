<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Todo App — Docker Swarm Demo">

    <title>{{ config('app.name', 'Todo App') }} — @yield('title', 'Dashboard')</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Inline CSS — production-ready design system -->
    <style>
        :root {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-card: #1e293b;
            --bg-input: #0f172a;
            --border: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent: #6366f1;
            --accent-hover: #4f46e5;
            --accent-light: rgba(99, 102, 241, 0.15);
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --radius: 10px;
            --shadow: 0 4px 24px rgba(0,0,0,0.3);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ---- Layout ---- */
        .layout { display: flex; min-height: 100vh; }

        /* ---- Sidebar ---- */
        .sidebar {
            width: 240px;
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 0;
            flex-shrink: 0;
        }

        .sidebar-logo {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo h1 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .sidebar-section {
            padding: 16px 12px 8px;
        }

        .sidebar-section-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0 8px;
            margin-bottom: 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.15s ease;
            font-size: 13.5px;
        }

        .nav-item:hover {
            background: var(--accent-light);
            color: var(--text-primary);
        }

        .nav-item.active {
            background: var(--accent-light);
            color: var(--accent);
        }

        .nav-item .nav-icon { font-size: 16px; width: 20px; text-align: center; }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 12px;
            border-top: 1px solid var(--border);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name { font-weight: 600; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .user-email { font-size: 11px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ---- Main content ---- */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .topbar-title {
            font-size: 16px;
            font-weight: 600;
        }

        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .content { padding: 28px; flex: 1; }

        /* ---- Buttons ---- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-primary { background: var(--accent); color: white; }
        .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-secondary { background: var(--bg-card); color: var(--text-secondary); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); color: var(--text-primary); }
        .btn-danger { background: rgba(239,68,68,0.15); color: var(--danger); border: 1px solid rgba(239,68,68,0.3); }
        .btn-danger:hover { background: rgba(239,68,68,0.25); }
        .btn-success { background: rgba(34,197,94,0.15); color: var(--success); border: 1px solid rgba(34,197,94,0.3); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }

        /* ---- Cards ---- */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .card-title { font-size: 15px; font-weight: 600; }

        /* ---- Form elements ---- */
        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-size: 12.5px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text-primary);
            padding: 9px 12px;
            font-size: 13.5px;
            font-family: inherit;
            transition: border-color 0.15s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        select.form-control { cursor: pointer; }

        textarea.form-control { resize: vertical; min-height: 90px; }

        .form-error { font-size: 11.5px; color: var(--danger); margin-top: 4px; }

        /* ---- Badges ---- */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-urgent { background: rgba(239,68,68,0.15); color: #ef4444; }
        .badge-high { background: rgba(249,115,22,0.15); color: #f97316; }
        .badge-medium { background: rgba(234,179,8,0.15); color: #eab308; }
        .badge-low { background: rgba(34,197,94,0.15); color: #22c55e; }
        .badge-pending { background: rgba(148,163,184,0.15); color: #94a3b8; }
        .badge-in_progress { background: rgba(99,102,241,0.15); color: #6366f1; }
        .badge-completed { background: rgba(34,197,94,0.15); color: #22c55e; }
        .badge-cancelled { background: rgba(239,68,68,0.15); color: #ef4444; }

        /* ---- Alerts ---- */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13.5px;
        }

        .alert-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3); color: #4ade80; }
        .alert-error { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }

        /* ---- Stats grid ---- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            text-align: center;
        }

        .stat-value { font-size: 28px; font-weight: 700; color: var(--accent); }
        .stat-label { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }

        /* ---- Table ---- */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 12px 12px;
            border-bottom: 1px solid rgba(51,65,85,0.5);
            vertical-align: middle;
        }

        tr:hover td { background: rgba(99,102,241,0.03); }

        tr.completed td { opacity: 0.55; }

        /* ---- Filters bar ---- */
        .filters-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .filters-bar .form-control {
            width: auto;
            min-width: 130px;
        }

        /* ---- Pagination ---- */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
        }

        .pagination a, .pagination span {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            border: 1px solid var(--border);
            color: var(--text-secondary);
            text-decoration: none;
        }

        .pagination a:hover { background: var(--accent-light); color: var(--accent); }
        .pagination .active span { background: var(--accent); color: white; border-color: var(--accent); }

        /* ---- Logout form ---- */
        .logout-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 13px;
            padding: 0;
            font-family: inherit;
        }

        .logout-btn:hover { color: var(--danger); }

        /* ---- Overdue indicator ---- */
        .overdue { color: var(--danger) !important; }

        /* ---- Animations ---- */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .content > * { animation: fadeIn 0.2s ease both; }

        /* ---- Responsive ---- */
        @media (max-width: 768px) {
            .sidebar { display: none; }
            .content { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="layout">

    {{-- ========== Sidebar ========== --}}
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h1>
                <span class="logo-icon">✓</span>
                TodoSwarm
            </h1>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">Menu</div>
            <a href="{{ route('todos.index') }}"
               class="nav-item {{ request()->routeIs('todos.*') ? 'active' : '' }}">
                <span class="nav-icon">📋</span> My Todos
            </a>
            <a href="{{ route('todos.create') }}"
               class="nav-item {{ request()->routeIs('todos.create') ? 'active' : '' }}">
                <span class="nav-icon">➕</span> New Todo
            </a>
            <a href="{{ route('categories.index') }}"
               class="nav-item {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <span class="nav-icon">🏷️</span> Categories
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-label">System</div>
            <a href="/horizon" target="_blank" class="nav-item">
                <span class="nav-icon">⚙️</span> Horizon
            </a>
            <a href="/health" target="_blank" class="nav-item">
                <span class="nav-icon">💚</span> Health Check
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="user-card">
                <div class="avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::user()->name }}</div>
                    <div class="user-email">{{ Auth::user()->email }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" style="margin-top:8px; padding: 0 8px;">
                @csrf
                <button type="submit" class="logout-btn">← Sign out</button>
            </form>
        </div>
    </aside>

    {{-- ========== Main ========== --}}
    <div class="main">
        <header class="topbar">
            <span class="topbar-title">@yield('title', 'Dashboard')</span>
            <div class="topbar-actions">
                <span style="font-size:11px; color:var(--text-muted);">
                    🖥 {{ gethostname() }}
                </span>
                <a href="{{ route('todos.create') }}" class="btn btn-primary btn-sm">
                    + New Todo
                </a>
            </div>
        </header>

        <div class="content">

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success">✓ {{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-error">✗ {{ session('error') }}</div>
            @endif

            @yield('content')
        </div>
    </div>

</div>
</body>
</html>
