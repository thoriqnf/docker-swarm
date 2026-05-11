@extends('layouts.app')

@section('title', 'Edit Todo')

@section('content')

<div style="max-width:640px;">
    <div class="card">
        <div class="card-header">
            <span class="card-title">✏️ Edit Todo</span>
            <a href="{{ route('todos.index') }}" class="btn btn-secondary btn-sm">← Back</a>
        </div>

        <form method="POST" action="{{ route('todos.update', $todo) }}">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label" for="title">Title <span style="color:var(--danger)">*</span></label>
                <input
                    id="title"
                    type="text"
                    name="title"
                    class="form-control"
                    value="{{ old('title', $todo->title) }}"
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
                >{{ old('description', $todo->description) }}</textarea>
                @error('description')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        @foreach(['low', 'medium', 'high', 'urgent'] as $p)
                            <option value="{{ $p }}" {{ old('priority', $todo->priority) === $p ? 'selected' : '' }}>
                                {{ ucfirst($p) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        @foreach(['pending', 'in_progress', 'completed', 'cancelled'] as $s)
                            <option value="{{ $s }}" {{ old('status', $todo->status) === $s ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                <div class="form-group">
                    <label class="form-label" for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-control">
                        <option value="">No category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $todo->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="due_date">Due Date</label>
                    <input
                        id="due_date"
                        type="date"
                        name="due_date"
                        class="form-control"
                        value="{{ old('due_date', $todo->due_date?->format('Y-m-d')) }}"
                    >
                </div>
            </div>

            {{-- Metadata --}}
            <div style="font-size:11.5px; color:var(--text-muted); margin-bottom:16px;">
                Created {{ $todo->created_at->diffForHumans() }}
                @if($todo->completed_at)
                    · Completed {{ $todo->completed_at->diffForHumans() }}
                @endif
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn btn-primary">✓ Save Changes</button>
                <a href="{{ route('todos.index') }}" class="btn btn-secondary">Cancel</a>

                {{-- Danger zone --}}
                <form method="POST" action="{{ route('todos.destroy', $todo) }}" style="margin-left:auto;"
                      onsubmit="return confirm('Delete this todo permanently?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">🗑 Delete</button>
                </form>
            </div>
        </form>
    </div>
</div>

@endsection
