<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name'     => 'Demo User',
                'password' => Hash::make('password'),
            ]
        );

        // Categories
        $categories = [
            ['name' => 'Work',      'color' => '#6366f1', 'icon' => '💼'],
            ['name' => 'Personal',  'color' => '#22c55e', 'icon' => '🏠'],
            ['name' => 'Learning',  'color' => '#f97316', 'icon' => '📚'],
            ['name' => 'Health',    'color' => '#ef4444', 'icon' => '❤️'],
        ];

        $createdCats = [];
        foreach ($categories as $catData) {
            $createdCats[] = Category::firstOrCreate(
                ['user_id' => $user->id, 'name' => $catData['name']],
                ['color' => $catData['color'], 'icon' => $catData['icon']]
            );
        }

        // Sample todos
        $todos = [
            [
                'title'       => 'Set up Docker Swarm cluster',
                'description' => 'Initialize 3 manager nodes and 2 worker nodes',
                'priority'    => 'urgent',
                'status'      => 'in_progress',
                'due_date'    => now()->addDays(1),
                'category'    => 0,
            ],
            [
                'title'       => 'Configure Docker Secrets',
                'description' => 'Move all env vars to docker secret create commands',
                'priority'    => 'high',
                'status'      => 'pending',
                'due_date'    => now()->addDays(2),
                'category'    => 0,
            ],
            [
                'title'       => 'Set up Traefik ingress',
                'description' => 'Configure load balancer with overlay network',
                'priority'    => 'medium',
                'status'      => 'pending',
                'due_date'    => now()->addDays(3),
                'category'    => 0,
            ],
            [
                'title'       => 'Demo rolling update',
                'description' => 'Build v2, deploy with zero downtime using curl loop',
                'priority'    => 'medium',
                'status'      => 'pending',
                'due_date'    => now()->addDays(4),
                'category'    => 0,
            ],
            [
                'title'       => 'Read Docker Swarm docs',
                'description' => 'Raft consensus, service mesh, overlay networks',
                'priority'    => 'low',
                'status'      => 'completed',
                'due_date'    => null,
                'category'    => 2,
            ],
            [
                'title'       => 'Morning run',
                'description' => '5km minimum',
                'priority'    => 'medium',
                'status'      => 'pending',
                'due_date'    => now()->addDays(1),
                'category'    => 3,
            ],
            [
                'title'       => 'Configure GitHub Actions CI/CD',
                'description' => 'Build → Test → Push → SSH deploy pipeline',
                'priority'    => 'high',
                'status'      => 'pending',
                'due_date'    => now()->addDays(5),
                'category'    => 0,
            ],
            [
                'title'       => 'Set up Portainer monitoring',
                'description' => 'Deploy Portainer CE to manager node',
                'priority'    => 'low',
                'status'      => 'pending',
                'due_date'    => now()->addDays(6),
                'category'    => 0,
            ],
            [
                'title'       => 'Overdue task example',
                'description' => 'This demonstrates overdue highlighting',
                'priority'    => 'high',
                'status'      => 'pending',
                'due_date'    => now()->subDays(2),
                'category'    => 1,
            ],
        ];

        foreach ($todos as $index => $todoData) {
            $category = $createdCats[$todoData['category']] ?? null;

            Todo::firstOrCreate(
                ['user_id' => $user->id, 'title' => $todoData['title']],
                [
                    'category_id' => $category?->id,
                    'description' => $todoData['description'],
                    'priority'    => $todoData['priority'],
                    'status'      => $todoData['status'],
                    'due_date'    => $todoData['due_date'],
                    'sort_order'  => $index,
                    'completed_at' => $todoData['status'] === 'completed' ? now() : null,
                ]
            );
        }

        $this->command->info("✓ Demo user: demo@example.com / password");
        $this->command->info("✓ {$user->todos()->count()} todos seeded");
    }
}
