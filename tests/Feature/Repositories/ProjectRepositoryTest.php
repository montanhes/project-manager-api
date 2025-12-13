<?php

namespace Tests\Feature\Repositories;

use App\Models\Project;
use App\Models\Task;
use App\Repositories\Eloquent\EloquentProjectRepository;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ProjectRepositoryInterface $repository;

    private const PROJECTS_COUNT = 3;
    private const TASKS_COUNT = 3;
    private const INVALID_PROJECT_ID = 999;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentProjectRepository();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_all_projects(): void
    {
        $this->createProjects(self::PROJECTS_COUNT);

        $projects = $this->repository->all();

        expect($projects)->toHaveCount(self::PROJECTS_COUNT);
        expect($projects)->each->toBeInstanceOf(Project::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_empty_collection_when_no_projects_exist(): void
    {
        $projects = $this->repository->all();

        expect($projects)->toHaveCount(0);
        expect($projects)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_creates_a_project_with_provided_data(): void
    {
        $projectName = 'Test Project';
        $data = ['name' => $projectName];

        $project = $this->repository->create($data);

        expect($project)->toBeInstanceOf(Project::class);
        expect($project->name)->toBe($projectName);
        expect($project->id)->not->toBeNull();
        $this->assertDatabaseHas('projects', ['name' => $projectName]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_finds_a_project_by_id(): void
    {
        $project = Project::factory()->create();

        $foundProject = $this->repository->find($project->id);

        expect($foundProject)->toBeInstanceOf(Project::class);
        expect($foundProject->id)->toBe($project->id);
        expect($foundProject->name)->toBe($project->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_loads_related_tasks_when_finding_a_project(): void
    {
        $project = Project::factory()->create();
        $this->createTasksForProject($project, self::TASKS_COUNT);

        $foundProject = $this->repository->find($project->id);

        expect($foundProject->tasks)->toHaveCount(self::TASKS_COUNT);
        expect($foundProject->tasks)->each->toBeInstanceOf(Task::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_throws_exception_when_project_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->find(self::INVALID_PROJECT_ID);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_preserves_project_relationships_when_finding(): void
    {
        $project = Project::factory()->create();
        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);

        $foundProject = $this->repository->find($project->id);

        expect($foundProject->tasks->pluck('id')->toArray())->toContain($task1->id, $task2->id);
    }

    /**
     * Helper method para criar múltiplos projetos
     */
    private function createProjects(int $count): void
    {
        Project::factory($count)->create();
    }

    /**
     * Helper method para criar tasks para um projeto
     */
    private function createTasksForProject(Project $project, int $count): void
    {
        Task::factory($count)->create(['project_id' => $project->id]);
    }
}
