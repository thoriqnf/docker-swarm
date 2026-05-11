@extends('layouts.app')

@section('title', 'Categories')

@section('content')

<div style="display:grid; grid-template-columns:1fr 360px; gap:20px; align-items:start;">

    {{-- Categories list --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">🏷️ Categories ({{ $categories->count() }})</span>
        </div>

        @if($categories->isEmpty())
            <div style="text-align:center; padding:40px 0; color:var(--text-muted);">
                <div style="font-size:40px; margin-bottom:10px;">🏷️</div>
                <p>No categories yet. Create one →</p>
            </div>
        @else
            <div style="display:flex; flex-direction:column; gap:8px;">
                @foreach($categories as $cat)
                <div style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    padding:12px 14px;
                    background:var(--bg-input);
                    border:1px solid var(--border);
                    border-radius:8px;
                ">
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="
                            width:36px; height:36px;
                            background: {{ $cat->color }}22;
                            border: 2px solid {{ $cat->color }};
                            border-radius:8px;
                            display:flex; align-items:center; justify-content:center;
                            font-size:18px;
                        ">{{ $cat->icon }}</span>
                        <div>
                            <div style="font-weight:600; font-size:14px;">{{ $cat->name }}</div>
                            <div style="font-size:11.5px; color:var(--text-muted);">
                                {{ $cat->todos_count }} todo(s)
                            </div>
                        </div>
                    </div>

                    <div style="display:flex; gap:6px;">
                        {{-- Inline edit (simple approach via modal-like expand) --}}
                        <form method="POST" action="{{ route('categories.destroy', $cat) }}"
                              onsubmit="return confirm('Delete category? Todos will be unlinked.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">🗑</button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Create form --}}
    <div class="card">
        <div class="card-header">
            <span class="card-title">+ New Category</span>
        </div>

        <form method="POST" action="{{ route('categories.store') }}">
            @csrf

            <div class="form-group">
                <label class="form-label" for="name">Name</label>
                <input
                    id="name"
                    type="text"
                    name="name"
                    class="form-control"
                    value="{{ old('name') }}"
                    placeholder="e.g. Work, Personal..."
                    required
                >
                @error('name')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="color">Color</label>
                <div style="display:flex; gap:10px; align-items:center;">
                    <input
                        id="color"
                        type="color"
                        name="color"
                        value="{{ old('color', '#6366f1') }}"
                        style="width:44px; height:36px; padding:2px; background:var(--bg-input); border:1px solid var(--border); border-radius:6px; cursor:pointer;"
                    >
                    <input
                        type="text"
                        id="color_text"
                        class="form-control"
                        value="{{ old('color', '#6366f1') }}"
                        placeholder="#6366f1"
                        style="flex:1"
                        oninput="document.getElementById('color').value=this.value"
                    >
                </div>
                @error('color')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="icon">Icon (emoji)</label>
                <input
                    id="icon"
                    type="text"
                    name="icon"
                    class="form-control"
                    value="{{ old('icon', '📁') }}"
                    placeholder="📁"
                >
                <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                    Paste any emoji: 💼 🏠 🎯 📚 🌟 ❤️
                </div>
                @error('icon')
                    <div class="form-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">
                + Create Category
            </button>
        </form>

        <script>
            document.getElementById('color').addEventListener('input', function() {
                document.getElementById('color_text').value = this.value;
            });
        </script>
    </div>

</div>

@endsection
