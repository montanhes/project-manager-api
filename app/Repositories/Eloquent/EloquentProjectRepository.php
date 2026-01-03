<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function all(): Collection
    {
        return Project::all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Project::select('projects.*')
            ->withProgress()
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
