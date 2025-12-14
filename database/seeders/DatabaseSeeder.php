<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        Project::factory()
            ->count(40)
            ->create()
            ->each(function (Project $project) {
                $project->tasks()->saveMany(
                    Task::factory(rand(0, 50))->make()
                );
            });
    }
}
