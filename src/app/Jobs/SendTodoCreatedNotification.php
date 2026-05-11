<?php

namespace App\Jobs;

use App\Models\Todo;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTodoCreatedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Maximum number of seconds the job can run.
     */
    public int $timeout = 30;

    public function __construct(
        public readonly Todo $todo,
        public readonly User $user,
    ) {}

    /**
     * Execute the job.
     * In a real app this would send an email/push notification.
     * For demo purposes: logs the event + simulates processing time.
     */
    public function handle(): void
    {
        // Simulate async work (makes Horizon dashboard interesting)
        sleep(2);

        Log::info('Todo created notification dispatched', [
            'todo_id'    => $this->todo->id,
            'todo_title' => $this->todo->title,
            'user_id'    => $this->user->id,
            'user_email' => $this->user->email,
            'priority'   => $this->todo->priority,
            'node'       => gethostname(), // shows which Swarm node processed this!
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Todo notification job failed', [
            'todo_id' => $this->todo->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
