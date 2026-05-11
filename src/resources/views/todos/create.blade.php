@extends('layouts.app')

@section('title', 'New Todo')

@section('content')

<div style="max-width:640px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">➕ Create New Todo</span>
            <a href="{{ route('todos.index') }}" class="btn btn-secondary btn-sm">← Back</a>
        </div>

        <form method="POST" action="{{ route('todos.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="title">Title <span style="color:var(--danger)">*</span></label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    class="form-control"
                    value="{{ old('title') }}"
                    placeholder="What needs to be done?"
                    required
                    autofocus
                >
                @error('title')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea
                    id="description"
                    name="description"
                    class="form-control"
                    placeholder="Add details (optional)..."
                >{{ old('description') }}</textarea>
                @error('description')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        @foreach(['low', 'medium', 'high', 'urgent'] as $p)
                            <option value="{{ $p }}" {{ old('priority', 'medium') === $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                        @endforeach
                    </select>
                    @error('priority')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        @foreach(['pending', 'in_progress', 'completed', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ old('status', 'pending') === $s ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">No category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="due_date">Due Date</label>
                    <input
                        id="due_date"
                        type="date"
                        name="due_date"
                        class="form-control"
                        value="{{ old('due_date') }}"
                        min="{{ date('Y-m-d') }}"
                    >
                    @error('due_date')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-top:8px;">
                <button type="submit" class="btn btn-primary">
                    ✓ Create Todo
                </button>
                <a href="{{ route('todos.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    @if($categories->isEmpty())
        <div style="margin-top:12px; padding:12px 16px; background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.2); border-radius:8px; font-size:13px; color:var(--text-secondary);">
            💡 No categories yet.
            <a href="{{ route('categories.index') }}" style="color:var(--accent);">Create a category</a> to organize your todos.
        </div>
    @endif
</div>

@endsection
