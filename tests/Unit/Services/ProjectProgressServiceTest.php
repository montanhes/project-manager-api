<?php

namespace Tests\Unit\Services;

use App\Enums\TaskDifficulty;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectProgressServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectProgressService $service;

    private static int $DIFFICULTY_LOW_VALUE;
    private static int $DIFFICULTY_LOW_EFFORT;
    private static int $DIFFICULTY_MEDIUM_VALUE;
    private static int $DIFFICULTY_MEDIUM_EFFORT;
    private static int $DIFFICULTY_HIGH_VALUE;
    private static int $DIFFICULTY_HIGH_EFFORT;

    private const PROGRESS_COMPLETE = 100.0;
    private const PROGRESS_NONE = 0.0;
    private const PROGRESS_HALF = 50.0;

    public static function setUpBeforeClass(): void
    {
        self::$DIFFICULTY_LOW_VALUE = TaskDifficulty::LOW->value;
        self::$DIFFICULTY_LOW_EFFORT = TaskDifficulty::LOW->points();
        self::$DIFFICULTY_MEDIUM_VALUE = TaskDifficulty::MEDIUM->value;
        self::$DIFFICULTY_MEDIUM_EFFORT = TaskDifficulty::MEDIUM->points();
        self::$DIFFICULTY_HIGH_VALUE = TaskDifficulty::HIGH->value;
        self::$DIFFICULTY_HIGH_EFFORT = TaskDifficulty::HIGH->points();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProjectProgressService();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_zero_when_project_has_no_tasks(): void
    {
        $project = Project::factory()->create();

        $progress = $this->service->calculate($project);

        expect($progress)->toBe(self::PROGRESS_NONE);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_zero_when_all_tasks_are_incomplete(): void
    {
        $project = Project::factory()->create();

        $this->createIncompleteTasks(
            project: $project,
            count: 3,
            difficulty: self::$DIFFICULTY_LOW_VALUE,
        );

        $progress = $this->service->calculate($project);

        expect($progress)->toBe(self::PROGRESS_NONE);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_100_when_all_tasks_are_completed(): void
    {
        $project = Project::factory()->create();

        $this->createCompletedTasks(
            project: $project,
            count: 3,
            difficulty: self::$DIFFICULTY_LOW_VALUE,
        );

        $progress = $this->service->calculate($project);

        expect($progress)->toBe(self::PROGRESS_COMPLETE);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_50_percent_progress_with_half_completed_tasks(): void
    {
        $project = Project::factory()->create();

        $this->createCompletedTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_LOW_VALUE,
        );

        $this->createIncompleteTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_LOW_VALUE,
        );

        $progress = $this->service->calculate($project);

        expect($progress)->toBe(self::PROGRESS_HALF);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_progress_considering_difficulty_levels(): void
    {
        $project = Project::factory()->create();

        $this->createCompletedTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_LOW_VALUE,
        );

        $this->createIncompleteTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_MEDIUM_VALUE,
        );

        $this->createCompletedTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_HIGH_VALUE,
        );

        $progress = $this->service->calculate($project);

        $expectedProgress = (
            (self::$DIFFICULTY_LOW_EFFORT + self::$DIFFICULTY_HIGH_EFFORT) /
            (self::$DIFFICULTY_LOW_EFFORT + self::$DIFFICULTY_MEDIUM_EFFORT + self::$DIFFICULTY_HIGH_EFFORT)
        ) * 100;

        expect($progress)->toBe(round($expectedProgress, 2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_rounds_progress_to_two_decimal_places(): void
    {
        $project = Project::factory()->create();

        $this->createCompletedTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_LOW_VALUE,
        );

        $this->createIncompleteTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_HIGH_VALUE,
        );

        $progress = $this->service->calculate($project);

        $expectedProgress = (
            self::$DIFFICULTY_LOW_EFFORT /
            (self::$DIFFICULTY_LOW_EFFORT + self::$DIFFICULTY_HIGH_EFFORT)
        ) * 100;

        expect($progress)->toBe(round($expectedProgress, 2));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_with_all_difficulty_levels(): void
    {
        $project = Project::factory()->create();

        $this->createIncompleteTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_LOW_VALUE,
        );

        $this->createCompletedTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_MEDIUM_VALUE,
        );

        $this->createCompletedTasks(
            project: $project,
            count: 1,
            difficulty: self::$DIFFICULTY_HIGH_VALUE,
        );

        $progress = $this->service->calculate($project);

        $expectedProgress = (
            (self::$DIFFICULTY_MEDIUM_EFFORT + self::$DIFFICULTY_HIGH_EFFORT) /
            (self::$DIFFICULTY_LOW_EFFORT + self::$DIFFICULTY_MEDIUM_EFFORT + self::$DIFFICULTY_HIGH_EFFORT)
        ) * 100;

        expect($progress)->toBe(round($expectedProgress, 2));
    }

    /**
     * Helper method para criar tarefas completadas
     */
    private function createCompletedTasks(
        Project $project,
        int $count,
        int $difficulty,
    ): void {
        Task::factory($count)->create([
            'project_id' => $project->id,
            'difficulty' => $difficulty,
            'completed' => true,
        ]);
    }

    /**
     * Helper method para criar tarefas incompletas
     */
    private function createIncompleteTasks(
        Project $project,
        int $count,
        int $difficulty,
    ): void {
        Task::factory($count)->create([
            'project_id' => $project->id,
            'difficulty' => $difficulty,
            'completed' => false,
        ]);
    }
}
