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

        $projects = Project::factory()
            ->count(40)
            ->create();

        $progressBar = $this->command->getOutput()->createProgressBar(count($projects));
        $progressBar->start();

        $projects->each(function (Project $project) use ($progressBar) {
            $project->tasks()->saveMany(
                Task::factory(rand(0, 50))->make()
            );
            $progressBar->advance();
        });

        $progressBar->finish();
        $this->command->getOutput()->newLine();
    }
}
