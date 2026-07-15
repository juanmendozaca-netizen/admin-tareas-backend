<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private readonly TaskService $taskService) {}

    
     // Devuelve las métricas de tareas del usuario autenticado.
     
    public function index(): JsonResponse
    {
        $metrics = $this->taskService->dashboardMetrics(Auth::user());

        return response()->json([
            'data' => $metrics,
        ]);
    }
}
