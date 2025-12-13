<?php

namespace Tests\Feature\Repositories;

use App\Enums\TaskDifficulty;
use App\Models\Project;
use App\Models\Task;
use App\Repositories\Eloquent\EloquentTaskRepository;
use App\Repositories\Interfaces\TaskRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TaskRepositoryInterface $repository;
    private Project $project;

    private const TASK_TITLE_SIMPLE = 'Test Task';
    private const TASK_TITLE_COMPLEX = 'Complex Task';
    private const TOGGLE_ITERATIONS = 3;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentTaskRepository();
        $this->project = Project::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_a_task_with_provided_data(): void
    {
        $data = $this->getBasicTaskData(self::TASK_TITLE_SIMPLE);

        $task = $this->repository->create($data);

        expect($task)->toBeInstanceOf(Task::class);
        expect($task->title)->toBe(self::TASK_TITLE_SIMPLE);
        expect($task->difficulty)->toBe(TaskDifficulty::LOW);
        expect($task->completed)->toBeFalse();
        $this->assertDatabaseHas('tasks', ['title' => self::TASK_TITLE_SIMPLE]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggles_task_completed_status_from_false_to_true(): void
    {
        $task = $this->createIncompleteTask();

        $toggledTask = $this->repository->toggle($task);

        expect($toggledTask->completed)->toBeTrue();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggles_task_completed_status_from_true_to_false(): void
    {
        $task = $this->createCompleteTask();

        $toggledTask = $this->repository->toggle($task);

        expect($toggledTask->completed)->toBeFalse();
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => false,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_persists_toggle_changes_to_database(): void
    {
        $task = $this->createIncompleteTask();
        $initialState = $task->completed;

        $this->repository->toggle($task);
        $refreshedTask = Task::findOrFail($task->id);

        expect($refreshedTask->completed)->toBe(!$initialState);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_the_task_instance_after_toggle(): void
    {
        $task = $this->createIncompleteTask();

        $result = $this->repository->toggle($task);

        expect($result)->toBeInstanceOf(Task::class);
        expect($result->id)->toBe($task->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_deletes_a_task(): void
    {
        $task = $this->createIncompleteTask();
        $taskId = $task->id;

        $result = $this->repository->delete($task);

        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_true_when_task_is_deleted_successfully(): void
    {
        $task = $this->createIncompleteTask();

        $result = $this->repository->delete($task);

        expect($result)->toBeTrue();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_affect_other_tasks_when_deleting(): void
    {
        $taskToDelete = $this->createIncompleteTask();
        $taskToKeep = $this->createIncompleteTask();

        $this->repository->delete($taskToDelete);

        $this->assertDatabaseHas('tasks', ['id' => $taskToKeep->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $taskToDelete->id]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_task_with_all_fillable_attributes(): void
    {
        $data = [
            'title' => self::TASK_TITLE_COMPLEX,
            'difficulty' => TaskDifficulty::HIGH->value,
            'completed' => false,
            'project_id' => $this->project->id,
        ];

        $task = $this->repository->create($data);

        expect($task->title)->toBe(self::TASK_TITLE_COMPLEX);
        expect($task->difficulty)->toBe(TaskDifficulty::HIGH);
        expect($task->completed)->toBeFalse();
        expect($task->project_id)->toBe($this->project->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggles_multiple_times_correctly(): void
    {
        $task = $this->createIncompleteTask();
        $expectedStates = [true, false, true];

        for ($i = 0; $i < self::TOGGLE_ITERATIONS; $i++) {
            $this->repository->toggle($task);
            expect($task->refresh()->completed)->toBe($expectedStates[$i]);
        }
    }

    /**
     * Helper method para criar dados básicos de task
     */
    private function getBasicTaskData(string $title): array
    {
        return [
            'title' => $title,
            'difficulty' => TaskDifficulty::LOW->value,
            'completed' => false,
            'project_id' => $this->project->id,
        ];
    }

    /**
     * Helper method para criar uma task incompleta
     */
    private function createIncompleteTask(): Task
    {
        return Task::factory()->create([
            'project_id' => $this->project->id,
            'completed' => false,
        ]);
    }

    /**
     * Helper method para criar uma task completa
     */
    private function createCompleteTask(): Task
    {
        return Task::factory()->create([
            'project_id' => $this->project->id,
            'completed' => true,
        ]);
    }
}
