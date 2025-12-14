<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Models\Task;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function all(): Collection
    {
        return Project::all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        $progressSubquery = Task::query()
            ->selectRaw('
                COALESCE(
                    ROUND(
                        SUM(
                            CASE WHEN completed = 1 THEN
                                CASE difficulty
                                    WHEN 1 THEN 1
                                    WHEN 2 THEN 4
                                    WHEN 3 THEN 12
                                    ELSE 0
                                END
                            ELSE 0
                            END
                        ) /
                        NULLIF(
                            SUM(
                                CASE difficulty
                                    WHEN 1 THEN 1
                                    WHEN 2 THEN 4
                                    WHEN 3 THEN 12
                                    ELSE 0
                                END
                            ),
                            0
                        ) * 100,
                        2
                    ),
                    0.0
                )
            ')
            ->whereColumn('project_id', 'projects.id');

        return Project::query()
            ->select('projects.*')
            ->addSelect(['progress' => $progressSubquery])
            ->paginate($perPage);
    }

    public function create(array $data)
    {
        return Project::create($data);
    }

    public function find(int $id): Project
    {
        return Project::with('tasks')->findOrFail($id);
    }
}
