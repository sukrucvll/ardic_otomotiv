<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployeeController extends Controller
{
    /**
     * GET /api/v1/employees?search=&department=&per_page=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.config('personnel.api.max_per_page')],
        ]);

        $employees = Employee::query()
            ->search($filters['search'] ?? null)
            ->inDepartment($filters['department'] ?? null)
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 25)
            ->withQueryString();

        return EmployeeResource::collection($employees);
    }

    /**
     * GET /api/v1/employees/{id}
     */
    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee);
    }
}
