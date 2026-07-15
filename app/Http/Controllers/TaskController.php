<?php
// app/Http/Controllers/TaskController.php

namespace App\Http\Controllers;

use App\Exceptions\TaskAlreadyDoneException;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    /**
     * GET /api/tasks
     * Filtros soportados: status, priority, search
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'priority', 'search']);

        $tasks = $this->taskService->listForUser(Auth::user(), $filters);

        return response()->json([
            'data' => TaskResource::collection($tasks),
        ]);
    }

    /**
     * POST /api/tasks
     */
    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create(Auth::user(), $request->validated());

        return response()->json([
            'data' => new TaskResource($task),
        ], 201);
    }

    /**
     * PUT/PATCH /api/tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);

        try {
            $task = $this->taskService->update($task, $request->validated());
        } catch (TaskAlreadyDoneException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * PATCH /api/tasks/{task}/status
     */
    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);

        $task = $this->taskService->updateStatus($task, $request->validated('status'));

        return response()->json([
            'data' => new TaskResource($task),
        ]);
    }

    /**
     * DELETE /api/tasks/{task}
     */
    public function destroy(Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);

        $this->taskService->delete($task);

        return response()->json(status: 204);
    }

    /**
     * Garantiza que el usuario autenticado sea el dueño de la tarea.
     */
    private function authorizeOwnership(Task $task): void
    {
        abort_if(
            $task->user_id !== Auth::id(),
            403,
            'No tienes permiso para acceder a esta tarea.'
        );
    }
}
