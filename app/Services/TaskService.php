<?php
// app/Services/TaskService.php

namespace App\Services;

use App\Exceptions\TaskAlreadyDoneException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Listar las tareas del usuario aplicando filtros opcionales.
     */
    public function listForUser(User $user, array $filters): Collection
    {
        return $user->tasks()
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn($query, $priority) => $query->where('priority', $priority))
            ->when($filters['search'] ?? null, fn($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->latest()
            ->get();
    }

    /**
     * Crear una tarea para el usuario autenticado.
     */
    public function create(User $user, array $data): Task
    {
        return $user->tasks()->create($data);
    }

    /**
     * Actualizar una tarea, respetando la regla de negocio:
     * una tarea "done" no puede ser editada.
     *
     * @throws TaskAlreadyDoneException
     */
    public function update(Task $task, array $data): Task
    {
        $this->ensureIsEditable($task);

        $task->update($data);

        return $task;
    }


    public function updateStatus(Task $task, string $status): Task
    {
        $task->update(['status' => $status]);

        return $task;
    }

    /**
     * Eliminar una tarea.
     */
    public function delete(Task $task): void
    {
        $task->delete();
    }

    
     //Obtener las métricas para el dashboard del usuario.
     
    public function dashboardMetrics(User $user): array
    {
        $counts = $user->tasks()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total' => (int) $counts->sum(),
            'pending' => (int) $counts->get('pending', 0),
            'in_progress' => (int) $counts->get('in_progress', 0),
            'done' => (int) $counts->get('done', 0),
        ];
    }


     //Verifica que la tarea pueda editarse.
    
    private function ensureIsEditable(Task $task): void
    {
        if ($task->status === 'done') {
            throw new TaskAlreadyDoneException();
        }
    }
}
