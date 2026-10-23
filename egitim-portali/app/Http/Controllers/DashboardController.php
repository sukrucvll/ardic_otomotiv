<?php

namespace App\Http\Controllers;

use App\Enums\AssignmentStatus;
use App\Models\Training;
use App\Models\TrainingAssignment;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $statusCounts = TrainingAssignment::toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalAssignments = (int) $statusCounts->sum();
        $completed = (int) ($statusCounts[AssignmentStatus::Completed->value] ?? 0);

        $stats = [
            'trainings' => Training::count(),
            'assignments' => $totalAssignments,
            'completed' => $completed,
            'completion_rate' => $totalAssignments ? (int) round($completed * 100 / $totalAssignments) : 0,
            'overdue' => TrainingAssignment::overdue()->count(),
        ];

        // Departman bilgisi atamadaki personel snapshot'ından gelir; API çağrısı gerekmez
        $departments = TrainingAssignment::toBase()
            ->selectRaw('employee_department as name, count(*) as total, sum(case when status = ? then 1 else 0 end) as completed', [AssignmentStatus::Completed->value])
            ->groupBy('employee_department')
            ->get()
            ->map(function ($row) {
                $row->rate = (int) round($row->completed * 100 / $row->total);

                return $row;
            })
            ->sortByDesc('rate')
            ->values();

        return view('dashboard', [
            'stats' => $stats,
            'statusCounts' => $statusCounts,
            'departments' => $departments,
            'trainings' => Training::withProgress()->orderByDesc('assignments_count')->take(6)->get(),
            'recent' => TrainingAssignment::with('training')->latest()->take(6)->get(),
            'upcoming' => TrainingAssignment::with('training')
                ->where('status', '!=', AssignmentStatus::Completed)
                ->whereNotNull('due_date')
                ->orderBy('due_date')
                ->take(5)
                ->get(),
        ]);
    }
}
