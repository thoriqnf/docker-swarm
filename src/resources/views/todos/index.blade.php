@extends('layouts.app')

@section('title', 'My Todos')

@section('content')

{{-- Stats Row --}}
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-label">Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#94a3b8">{{ $stats['pending'] }}</div>
        <div class="stat-label">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#6366f1">{{ $stats['in_progress'] }}</div>
        <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#22c55e">{{ $stats['completed'] }}</div>
        <div class="stat-label">Completed</div>
    </div>
    <div class="stat-card">
        <div class="stat-value" style="color:#ef4444">{{ $stats['overdue'] }}</div>
        <div class="stat-label">Overdue</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:16px; padding:14px 18px;">
    <form method="GET" action="{{ route('todos.index') }}">
        <div class="filters-bar">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="🔍 Search todos..."
                value="{{ $filters['search'] ?? '' }}"
                style="min-width:200px; flex:1"
            >

            <select name="status" class="form-control" onchange="this.form.submit()">
                <option value="">All Status</option>
                @foreach(['pending', 'in_progress', 'completed', 'cancelled'] as $s)
                    <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                    </option>
                @endforeach
            </select>

            <select name="priority" class="form-control" onchange="this.form.submit()">
                <option value="">All Priority</option>
                @foreach(['urgent', 'high', 'medium', 'low'] as $p)
                    <option value="{{ $p }}" {{ ($filters['priority'] ?? '') === $p ? 'selected' : '' }}>
                        {{ ucfirst($p) }}
                    </option>
                @endforeach
            </select>

            <select name="category_id" class="form-control" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <input
                type="date"
                name="due_date"
                class="form-control"
                value="{{ $filters['due_date'] ?? '' }}"
                onchange="this.form.submit()"
            >

            @if(array_filter($filters))
                <a href="{{ route('todos.index') }}" class="btn btn-secondary">✕ Clear</a>
            @else
                <button type="submit" class="btn btn-secondary">Search</button>
            @endif
        </div>
    </form>
</div>

{{-- Todo Table --}}
<div class="card">
    <div class="card-header">
        <span class="card-title">📋 Todos ({{ $todos->total() }})</span>
        <a href="{{ route('todos.create') }}" class="btn btn-primary btn-sm">+ Add Todo</a>
    </div>

    @if($todos->isEmpty())
        <div style="text-align:center; padding:48px 0; color:var(--text-muted);">
            <div style="font-size:48px; margin-bottom:12px;">📭</div>
            <p style="font-size:15px;">No todos yet.</p>
            <a href="{{ route('todos.create') }}" class="btn btn-primary" style="margin-top:16px;">Create your first todo</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($todos as $todo)
                    <tr class="{{ $todo->status === 'completed' ? 'completed' : '' }}">
                        <td>
                            <div style="font-weight:500; max-width:320px;">
                                {{ $todo->title }}
                            </div>
                            @if($todo->description)
                                <div style="font-size:11.5px; color:var(--text-muted); margin-top:2px;">
                                    {{ Str::limit($todo->description, 60) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if($todo->category)
                                <span style="display:inline-flex; align-items:center; gap:5px; font-size:12.5px;">
                                    <span style="width:8px; height:8px; border-radius:50%; background:{{ $todo->category->color }};"></span>
                                    {{ $todo->category->name }}
                                </span>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-{{ $todo->priority }}">
                                {{ strtoupper($todo->priority) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-{{ $todo->status }}">
                                {{ str_replace('_', ' ', strtoupper($todo->status)) }}
                            </span>
                        </td>
                        <td>
                            @if($todo->due_date)
                                <span class="{{ $todo->is_overdue ? 'overdue' : '' }}" style="font-size:12.5px;">
                                    {{ $todo->is_overdue ? '⚠️ ' : '' }}{{ $todo->due_date->format('M d, Y') }}
                                </span>
                            @else
                                <span style="color:var(--text-muted)">—</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex; gap:6px; align-items:center;">
                                {{-- Toggle complete --}}
                                <form method="POST" action="{{ route('todos.toggle', $todo) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button
                                        type="submit"
                                        class="btn btn-sm {{ $todo->status === 'completed' ? 'btn-secondary' : 'btn-success' }}"
                                        title="{{ $todo->status === 'completed' ? 'Reopen' : 'Mark complete' }}"
                                    >
                                        {{ $todo->status === 'completed' ? '↩' : '✓' }}
                                    </button>
                                </form>

                                <a href="{{ route('todos.edit', $todo) }}" class="btn btn-secondary btn-sm">✏</a>

                                <form method="POST" action="{{ route('todos.destroy', $todo) }}" style="display:inline;"
                                      onsubmit="return confirm('Delete this todo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pagination">
            {{ $todos->links('pagination::simple-bootstrap-5') }}
        </div>
    @endif
</div>

@endsection
