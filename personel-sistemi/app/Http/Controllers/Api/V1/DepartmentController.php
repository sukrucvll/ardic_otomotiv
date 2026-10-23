<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * GET /api/v1/departments
     */
    public function __invoke(): JsonResponse
    {
        $counts = Employee::query()
            ->selectRaw('department, count(*) as total')
            ->groupBy('department')
            ->pluck('total', 'department');

        $departments = collect(config('personnel.departments'))
            ->keys()
            ->map(fn (string $name) => [
                'name' => $name,
                'employee_count' => (int) ($counts[$name] ?? 0),
            ])
            ->values();

        return response()->json(['data' => $departments]);
    }
}
