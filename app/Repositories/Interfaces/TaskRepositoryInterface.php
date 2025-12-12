<?php

namespace App\Repositories\Interfaces;

use App\Models\Task;

interface TaskRepositoryInterface
{
    public function create(array $data);

    public function toggle(Task $task);

    public function delete(Task $task);
}
