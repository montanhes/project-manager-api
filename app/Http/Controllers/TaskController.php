<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;

class TaskController extends Controller
{
    protected $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTaskRequest $request)
    {
        $task = $this->taskRepository->create($request->validated());
        return response()->json($task, 201);
    }

    /**
     * Toggle the completed status of the specified task.
     */
    public function toggle(Task $task)
    {
        $updatedTask = $this->taskRepository->toggle($task);
        return response()->json($updatedTask);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $this->taskRepository->delete($task);
        return response()->noContent();
    }
}
