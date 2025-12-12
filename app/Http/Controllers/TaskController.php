<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Http\Request;

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
    public function store(Request $request)
    {
        $task = $this->taskRepository->create($request->all());
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
