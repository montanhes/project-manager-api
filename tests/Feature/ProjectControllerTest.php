<?php

namespace Tests\Feature;

use App\Enums\TaskDifficulty;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PROJECT_NAME = 'Test Project';
    private const PROJECT_NAME_UPDATED = 'Updated Project';
    private const INVALID_PROJECT_ID = 999;

    private const DEFAULT_TOTAL_TASKS = 3;
    private const DEFAULT_COMPLETED_TASKS = 1;
    private const PROJECT_INDEX_COUNT = 3;
    private const PROGRESS_FIFTY_PERCENT = 50.0;
    private const PROGRESS_ZERO_PERCENT = 0.0;
    private const PROGRESS_WEIGHTED = 76.47;

    private const WEIGHTED_TOTAL_TASKS = 3;
    private const WEIGHTED_LOW_COMPLETED = true;
    private const WEIGHTED_MEDIUM_COMPLETED = false;
    private const WEIGHTED_HIGH_COMPLETED = true;

    private static int $DIFFICULTY_LOW_VALUE;
    private static int $DIFFICULTY_MEDIUM_VALUE;
    private static int $DIFFICULTY_HIGH_VALUE;

    private User $user;

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
    }

    private function getProjectData(string $name = self::PROJECT_NAME): array
    {
        return ['name' => $name];
    }

    private function createProject(): Project
    {
        return Project::factory()->create();
    }

    private function createCompleteTask(Project $project, int $difficulty): Task
    {
        return Task::factory()->create([
            'project_id' => $project->id,
            'difficulty' => $difficulty,
            'completed' => true,
        ]);
    }

    private function createIncompleteTask(Project $project, int $difficulty): Task
    {
        return Task::factory()->create([
            'project_id' => $project->id,
            'difficulty' => $difficulty,
            'completed' => false,
        ]);
    }

    private function createProjectWithTasks(int $totalTasks = self::DEFAULT_TOTAL_TASKS, int $completedTasks = self::DEFAULT_COMPLETED_TASKS): Project
    {
        $project = $this->createProject();

        for ($i = 0; $i < $totalTasks; $i++) {
            Task::factory()->create([
                'project_id' => $project->id,
                'difficulty' => self::$DIFFICULTY_LOW_VALUE,
                'completed' => $i < $completedTasks,
            ]);
        }

        return $project;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_index_returns_all_projects_without_authentication(): void
    {
        $response = $this->getJson('/api/projects');
        $response->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_index_returns_all_projects_with_authentication(): void
    {
        Project::factory()->count(self::PROJECT_INDEX_COUNT)->create();

        $response = $this->actingAs($this->user)->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonIsArray()
            ->assertJsonCount(self::PROJECT_INDEX_COUNT);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_index_returns_empty_array_when_no_projects(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/projects');

        $response->assertOk()
            ->assertJson([]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_store_requires_authentication(): void
    {
        $response = $this->postJson('/api/projects', $this->getProjectData());

        $response->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_store_creates_project_with_valid_data(): void
    {
        $projectData = $this->getProjectData();

        $response = $this->actingAs($this->user)
            ->postJson('/api/projects', $projectData);

        $response->assertCreated()
            ->assertJsonFragment(['name' => self::PROJECT_NAME]);

        $this->assertDatabaseHas('projects', ['name' => self::PROJECT_NAME]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_store_returns_created_project_data(): void
    {
        $projectData = $this->getProjectData();

        $response = $this->actingAs($this->user)
            ->postJson('/api/projects', $projectData);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'name', 'created_at', 'updated_at']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_show_requires_authentication(): void
    {
        $project = $this->createProject();

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertUnauthorized();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_show_returns_project_with_progress(): void
    {
        $project = $this->createProjectWithTasks(totalTasks: 2, completedTasks: 1);

        $response = $this->actingAs($this->user)->getJson("/api/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonStructure(['id', 'name', 'progress', 'created_at', 'updated_at'])
            ->assertJsonPath('name', $project->name);

        $this->assertEquals(self::PROGRESS_FIFTY_PERCENT, (float) $response->json('progress'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_show_returns_zero_progress_when_no_tasks(): void
    {
        $project = $this->createProject();

        $response = $this->actingAs($this->user)->getJson("/api/projects/{$project->id}");

        $response->assertOk();
        $this->assertEquals(self::PROGRESS_ZERO_PERCENT, (float) $response->json('progress'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_show_calculates_progress_with_weighted_difficulty(): void
    {
        $project = $this->createProject();

        $this->createCompleteTask($project, self::$DIFFICULTY_LOW_VALUE);
        $this->createIncompleteTask($project, self::$DIFFICULTY_MEDIUM_VALUE);
        $this->createCompleteTask($project, self::$DIFFICULTY_HIGH_VALUE);

        $response = $this->actingAs($this->user)->getJson("/api/projects/{$project->id}");

        $response->assertOk();
        $this->assertEquals(self::PROGRESS_WEIGHTED, (float) $response->json('progress'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_show_returns_not_found_for_nonexistent_project(): void
    {
        $response = $this->actingAs($this->user)->getJson("/api/projects/" . self::INVALID_PROJECT_ID);

        $response->assertNotFound();
    }
}
