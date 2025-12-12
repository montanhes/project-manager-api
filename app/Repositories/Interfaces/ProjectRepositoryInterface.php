<?php

namespace App\Repositories\Interfaces;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    public function all(): Collection;

    public function create(array $data);

    public function find(int $id): Project;
}
