<?php

namespace App\Http\Controllers;

use App\Jobs\SendTodoCreatedNotification;
use App\Models\Category;
use App\Models\Todo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TodoController extends Controller
{
    /**
     * Display list of todos with filters.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'priority', 'category_id', 'search', 'due_date']);

        $todos = Todo::with('category')
            ->forUser(Auth::id())
            ->filter($filters)
            ->orderBy('sort_order')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('user_id', Auth::id())
            ->withCount('todos')
            ->get();

        $stats = [
            'total'       => Todo::forUser(Auth::id())->count(),
            'pending'     => Todo::forUser(Auth::id())->where('status', 'pending')->count(),
            'in_progress' => Todo::forUser(Auth::id())->where('status', 'in_progress')->count(),
            'completed'   => Todo::forUser(Auth::id())->where('status', 'completed')->count(),
            'overdue'     => Todo::forUser(Auth::id())
                ->whereDate('due_date', '<', today())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count(),
        ];

        return view('todos.index', compact('todos', 'categories', 'filters', 'stats'));
    }

    /**
     * Show create form.
     */
    public function create(): View
    {
        $categories = Category::where('user_id', Auth::id())->get();

        return view('todos.create', compact('categories'));
    }

    /**
     * Store a new todo.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority'    => 'required|in:low,medium,high,urgent',
            'status'      => 'required|in:pending,in_progress,completed,cancelled',
            'category_id' => 'nullable|exists:categories,id',
            'due_date'    => 'nullable|date|after_or_equal:today',
        ]);

        $todo = Todo::create([
            ...$validated,
            'user_id' => Auth::id(),
        ]);

        // Dispatch queue job — exercises Horizon worker
        SendTodoCreatedNotification::dispatch($todo, Auth::user())
            ->onQueue('notifications');

        return redirect()
            ->route('todos.index')
            ->with('success', "Todo \"{$todo->title}\" created successfully!");
    }

    /**
     * Show edit form.
     */
    public function edit(Todo $todo): View
    {
        $this->authorize('update', $todo);

        $categories = Category::where('user_id', Auth::id())->get();

        return view('todos.edit', compact('todo', 'categories'));
    }

    /**
     * Update an existing todo.
     */
    public function update(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'priority'    => 'required|in:low,medium,high,urgent',
            'status'      => 'required|in:pending,in_progress,completed,cancelled',
            'category_id' => 'nullable|exists:categories,id',
            'due_date'    => 'nullable|date',
        ]);

        // Auto-set completed_at when status changes to completed
        if ($validated['status'] === 'completed' && $todo->status !== 'completed') {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== 'completed') {
            $validated['completed_at'] = null;
        }

        $todo->update($validated);

        return redirect()
            ->route('todos.index')
            ->with('success', "Todo \"{$todo->title}\" updated successfully!");
    }

    /**
     * Delete a todo (soft delete).
     */
    public function destroy(Todo $todo): RedirectResponse
    {
        $this->authorize('delete', $todo);

        $title = $todo->title;
        $todo->delete();

        return redirect()
            ->route('todos.index')
            ->with('success', "Todo \"{$title}\" deleted.");
    }

    /**
     * Toggle complete/incomplete quickly.
     */
    public function toggle(Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        if ($todo->status === 'completed') {
            $todo->reopen();
        } else {
            $todo->markComplete();
        }

        return back()->with('success', 'Todo status updated.');
    }
}
