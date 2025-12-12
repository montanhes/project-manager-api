<?php

namespace App\Services;

use App\Models\Project;

class ProjectProgressService
{
    public function calculate(Project $project): float
    {
        $stats = $project->tasks()
            ->selectRaw('
                SUM(CASE difficulty
                    WHEN 1 THEN 1
                    WHEN 2 THEN 4
                    WHEN 3 THEN 12
                    ELSE 0
                END) as total_effort,
                SUM(CASE WHEN completed = 1 THEN (
                    CASE difficulty
                        WHEN 1 THEN 1
                        WHEN 2 THEN 4
                        WHEN 3 THEN 12
                        ELSE 0
                    END
                ) ELSE 0 END) as completed_effort
            ')
            ->first();

        $totalEffort = $stats->total_effort ?? 0;
        $completedEffort = $stats->completed_effort ?? 0;

        if ($totalEffort === 0) {
            return 0.0;
        }

        return round(($completedEffort / $totalEffort) * 100, 2);
    }
}
