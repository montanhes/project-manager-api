<?php

namespace App\Repositories\Eloquent;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function create(array $data)
    {
        return Task::create($data);
    }

    public function toggle(Task $task)
    {
        $task->completed = !$task->completed;
        $task->save();

        return $task;
    }

    public function delete(Task $task)
    {
        return $task->delete();
    }
}
