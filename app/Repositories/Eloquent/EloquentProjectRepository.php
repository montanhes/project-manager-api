<?php

namespace App\Repositories\Eloquent;

use App\Models\Project;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentProjectRepository implements ProjectRepositoryInterface
{
    public function all(): Collection
    {
        return Project::all();
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
