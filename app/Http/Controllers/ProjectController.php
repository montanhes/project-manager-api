<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Repositories\Interfaces\ProjectRepositoryInterface;
use App\Services\ProjectProgressService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    protected $projectRepository;
    protected $progressService;

    public function __construct(
        ProjectRepositoryInterface $projectRepository,
        ProjectProgressService $progressService
    ) {
        $this->projectRepository = $projectRepository;
        $this->progressService = $progressService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json($this->projectRepository->all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request)
    {
        $project = $this->projectRepository->create($request->validated());
        return response()->json($project, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $project = $this->projectRepository->find($id);
        $progress = $this->progressService->calculate($project);

        $projectData = $project->toArray();
        $projectData['progress'] = $progress;

        return response()->json($projectData);
    }
}
