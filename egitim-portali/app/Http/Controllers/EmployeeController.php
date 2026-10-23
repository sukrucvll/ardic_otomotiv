<?php

namespace App\Http\Controllers;

use App\Actions\SyncEmployeeSnapshots;
use App\Enums\AssignmentStatus;
use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Services\Personnel\EmployeeData;
use App\Services\Personnel\PersonnelApiClient;
use App\Services\Personnel\PersonnelApiException;
use App\Support\TurkishText;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Personel listesi Personel Sistemi API'sinden okunur; portal yalnızca
 * eğitim atamalarını kendi veritabanında tutar.
 */
class EmployeeController extends Controller
{
    public function index(Request $request, PersonnelApiClient $personnel): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
        ]);

        $employees = collect();
        $departments = collect();
        $personnelUnavailable = false;

        try {
            $employees = $personnel->employees();
            $departments = $personnel->departments();
        } catch (PersonnelApiException) {
            $personnelUnavailable = true;
        }

        $search = TurkishText::lower(trim((string) ($filters['search'] ?? '')));
        $department = $filters['department'] ?? null;

        $filtered = $employees
            ->when($search !== '', fn ($items) => $items->filter(
                fn (EmployeeData $employee) => Str::contains(TurkishText::lower($employee->fullName().' '.$employee->email), $search)
            ))
            ->when($department, fn ($items) => $items->where('department', $department))
            ->sort(fn (EmployeeData $a, EmployeeData $b) => TurkishText::compare($a->fullName(), $b->fullName()))
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $filtered->forPage($page, 15),
            $filtered->count(),
            15,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $progress = TrainingAssignment::toBase()
            ->selectRaw('employee_id, count(*) as total, sum(case when status = ? then 1 else 0 end) as completed', [AssignmentStatus::Completed->value])
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        return view('employees.index', [
            'employees' => $paginator,
            'departments' => $departments,
            'progress' => $progress,
            'personnelUnavailable' => $personnelUnavailable,
        ]);
    }

    public function show(int $employee, PersonnelApiClient $personnel, SyncEmployeeSnapshots $sync): View
    {
        $assignments = TrainingAssignment::with('training')
            ->where('employee_id', $employee)
            ->latest()
            ->get();

        $data = null;
        $personnelUnavailable = false;

        try {
            $data = $personnel->find($employee);
        } catch (PersonnelApiException) {
            $personnelUnavailable = true;
        }

        abort_if($data === null && ! $personnelUnavailable && $assignments->isEmpty(), 404);

        if ($data) {
            $sync->handle([$data]);
            $assignments->each->refresh();
        }

        // API'ye ulaşılamadıysa veya personel silindiyse son bilinen bilgi (snapshot) gösterilir
        $snapshot = $assignments->first();

        return view('employees.show', [
            'employeeId' => $employee,
            'employee' => $data,
            'snapshot' => $snapshot,
            'personnelUnavailable' => $personnelUnavailable,
            'assignments' => $assignments,
            'statusCounts' => $assignments->countBy(fn ($assignment) => $assignment->status->value),
            'availableTrainings' => Training::whereNotIn('id', $assignments->pluck('training_id'))->orderByRaw('title collate turkish')->get(),
        ]);
    }
}
