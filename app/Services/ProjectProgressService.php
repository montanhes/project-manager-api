<?php

namespace App\Services;

use App\Models\Project;

class ProjectProgressService
{
    public function calculate(Project $project): float
    {
        $result = Project::withProgress()
            ->where('id', $project->id)
            ->toBase()
            ->first();

        return (float) ($result->progress ?? 0.0);
    }
}
