<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'max:100'],
        ]);

        $employees = Employee::query()
            ->search($filters['search'] ?? null)
            ->inDepartment($filters['department'] ?? null)
            ->orderByName()
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => Employee::count(),
            'departments' => Employee::distinct()->count('department'),
            'this_month' => Employee::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('employees.index', [
            'employees' => $employees,
            'stats' => $stats,
            'departments' => config('personnel.departments'),
        ]);
    }

    public function create(): View
    {
        return view('employees.create', [
            'employee' => new Employee,
            'departments' => config('personnel.departments'),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        // Daha önce silinmiş bir personel aynı e-postayla tekrar eklenirse kaydı geri
        // yüklenir. ID aynı kaldığı için Eğitim Portalı'ndaki atama geçmişi de geri bağlanır.
        $employee = Employee::onlyTrashed()->firstWhere('email', $request->validated('email'));

        if ($employee) {
            $employee->restore();
            $employee->update($request->validated());

            return redirect()->route('employees.index')
                ->with('success', "{$employee->full_name} yeniden personel listesine eklendi.");
        }

        $employee = Employee::create($request->validated());

        return redirect()->route('employees.index')
            ->with('success', "{$employee->full_name} personel listesine eklendi.");
    }

    public function edit(Employee $employee): View
    {
        return view('employees.edit', [
            'employee' => $employee,
            'departments' => config('personnel.departments'),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('employees.index')
            ->with('success', "{$employee->full_name} bilgileri güncellendi.");
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', "{$employee->full_name} personel listesinden kaldırıldı.");
    }
}
