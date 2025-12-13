<?php

namespace Tests\Feature;

use App\Enums\TaskDifficulty;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TASK_TITLE = 'Test Task';
    private const TASK_TITLE_UPDATED = 'Updated Task';
    private const TASK_TITLE_TO_DELETE = 'Task to Delete';
    private const TASK_TITLE_TO_KEEP = 'Task to Keep';
    private const TASK_DESCRIPTION = 'Task description';
    private const INVALID_TASK_ID = 999;
    private const TOGGLE_ITERATIONS = 3;
    private const EXPECTED_DIFFICULTIES_COUNT = 3;

    private static int $DIFFICULTY_LOW_VALUE;
    private static int $DIFFICULTY_MEDIUM_VALUE;
    private static int $DIFFICULTY_HIGH_VALUE;

    private User $user;
    private Project $project;

    public static function setUpBeforeClass(): void
    {
        self::$DIFFICULTY_LOW_VALUE = TaskDifficulty::LOW->value;
        self::$DIFFICULTY_MEDIUM_VALUE = TaskDifficulty::MEDIUM->value;
        self::$DIFFICULTY_HIGH_VALUE = TaskDifficulty::HIGH->value;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create();
    }

    private function getBasicTaskData(
        string $title = self::TASK_TITLE,
        ?int $difficulty = null,
        ?int $projectId = null
    ): array {
        return [
            'title' => $title,
            'difficulty' => $difficulty ?? self::$DIFFICULTY_LOW_VALUE,
            'project_id' => $projectId ?? $this->project->id,
        ];
    }

    private function createIncompleteTask(string $title = self::TASK_TITLE): Task
    {
        return Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => $title,
            'completed' => false,
        ]);
    }

    private function createCompleteTask(string $title = self::TASK_TITLE): Task
    {
        return Task::factory()->create([
            'project_id' => $this->project->id,
            'title' => $title,
            'completed' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/tasks', $this->getBasicTaskData());

        $response->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_store_creates_task_with_valid_data(): void
    {
        $taskData = $this->getBasicTaskData();

        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks', $taskData);

        $response->assertCreated()
            ->assertJsonFragment(['title' => self::TASK_TITLE]);

        $this->assertDatabaseHas('tasks', [
            'title' => self::TASK_TITLE,
            'project_id' => $this->project->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_store_returns_created_task_with_completed_false(): void
    {
        $taskData = $this->getBasicTaskData();

        $response = $this->actingAs($this->user)
            ->postJson('/api/tasks', $taskData);

        $response->assertCreated();

        $this->assertDatabaseHas('tasks', [
            'title' => self::TASK_TITLE,
            'completed' => false,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_store_creates_task_with_different_difficulties(): void
    {
        $difficulties = [
            self::$DIFFICULTY_LOW_VALUE,
            self::$DIFFICULTY_MEDIUM_VALUE,
            self::$DIFFICULTY_HIGH_VALUE,
        ];

        foreach ($difficulties as $difficulty) {
            $taskData = $this->getBasicTaskData(difficulty: $difficulty);

            $response = $this->actingAs($this->user)
                ->postJson('/api/tasks', $taskData);

            $response->assertCreated()
                ->assertJsonPath('difficulty', $difficulty);
        }

        $this->assertDatabaseCount('tasks', self::EXPECTED_DIFFICULTIES_COUNT);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggle_requires_authentication(): void
    {
        $task = $this->createIncompleteTask();

        $response = $this->patchJson("/api/tasks/{$task->id}/toggle");

        $response->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggle_changes_completed_from_false_to_true(): void
    {
        $task = $this->createIncompleteTask();
        $this->assertFalse($task->completed);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('completed', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggle_changes_completed_from_true_to_false(): void
    {
        $task = $this->createCompleteTask();
        $this->assertTrue($task->completed);

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/toggle");

        $response->assertOk()
            ->assertJsonPath('completed', false);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => false,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggle_multiple_times_alternates_status(): void
    {
        $task = $this->createIncompleteTask();
        $expectedStates = [true, false, true];

        for ($i = 0; $i < self::TOGGLE_ITERATIONS; $i++) {
            $response = $this->actingAs($this->user)
                ->patchJson("/api/tasks/{$task->id}/toggle");

            $response->assertOk()
                ->assertJsonPath('completed', $expectedStates[$i]);

            $task->refresh();
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_toggle_returns_updated_task_data(): void
    {
        $task = $this->createIncompleteTask();

        $response = $this->actingAs($this->user)
            ->patchJson("/api/tasks/{$task->id}/toggle");

        $response->assertOk()
            ->assertJsonStructure(['id', 'title', 'difficulty', 'project_id', 'completed', 'created_at', 'updated_at']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_destroy_requires_authentication(): void
    {
        $task = $this->createIncompleteTask();

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_destroy_deletes_task(): void
    {
        $task = $this->createIncompleteTask();
        $taskId = $task->id;

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$taskId}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_destroy_returns_no_content_status(): void
    {
        $task = $this->createIncompleteTask();

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertNoContent();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_destroy_removes_only_specified_task(): void
    {
        $taskToDelete = $this->createIncompleteTask(self::TASK_TITLE_TO_DELETE);
        $taskToKeep = $this->createIncompleteTask(self::TASK_TITLE_TO_KEEP);

        $this->actingAs($this->user)->deleteJson("/api/tasks/{$taskToDelete->id}");

        $this->assertDatabaseMissing('tasks', ['id' => $taskToDelete->id]);
        $this->assertDatabaseHas('tasks', ['id' => $taskToKeep->id]);
    }
}
