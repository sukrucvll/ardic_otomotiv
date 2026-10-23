<?php

namespace App\Http\Controllers;

use App\Actions\AssignTraining;
use App\Enums\AssignmentStatus;
use App\Http\Requests\AssignEmployeesRequest;
use App\Http\Requests\AssignTrainingsRequest;
use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Services\Personnel\PersonnelApiClient;
use App\Services\Personnel\PersonnelApiException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TrainingAssignmentController extends Controller
{
    public function index(Request $request, PersonnelApiClient $personnel): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'max:20'],
            'training' => ['nullable', 'integer'],
            'department' => ['nullable', 'string', 'max:100'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        // Departman/isim filtreleri atamadaki bilgiye göre çalışır; önbellek süresi
        // dolduysa liste yenilenip bu bilgiler güncellenir. API kapalıysa sayfa yine açılır.
        try {
            $personnel->employees();
        } catch (PersonnelApiException) {
        }

        $status = $filters['status'] ?? null;

        $assignments = TrainingAssignment::with('training')
            ->when($status === 'overdue', fn ($query) => $query->overdue())
            ->when(AssignmentStatus::tryFrom((string) $status), fn ($query, $status) => $query->where('status', $status))
            ->when($filters['training'] ?? null, fn ($query, $id) => $query->where('training_id', $id))
            ->when($filters['department'] ?? null, fn ($query, $department) => $query->where('employee_department', $department))
            ->search($filters['search'] ?? null)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('assignments.index', [
            'assignments' => $assignments,
            'trainings' => Training::orderByRaw('title collate turkish')->get(['id', 'title']),
            'departments' => TrainingAssignment::distinct()->orderByRaw('employee_department collate turkish')->pluck('employee_department'),
        ]);
    }

    /**
     * Eğitim sayfasından: seçilen personele eğitimi ata.
     */
    public function store(AssignEmployeesRequest $request, Training $training, PersonnelApiClient $personnel, AssignTraining $assign): RedirectResponse
    {
        $ids = collect($request->validated('employee_ids'))->map(fn ($id) => (int) $id);

        // Formdan gelen ID'lere güvenilmez; personel bilgisi API'den doğrulanarak alınır.
        // Önbellek atlanır: bu arada silinen veya bilgisi değişen personele eski veriyle atama yapılmaz.
        try {
            $employees = $personnel->employees(fresh: true)->only($ids->all());
        } catch (PersonnelApiException) {
            return back()->with('error', PersonnelApiException::USER_MESSAGE);
        }

        if ($employees->count() !== $ids->count()) {
            return back()->withInput()->withErrors([
                'employee_ids' => 'Seçilen personellerden bazıları Personel Sistemi\'nde bulunamadı. Listeyi yenileyip tekrar deneyin.',
            ]);
        }

        $created = $assign->handle($training, $employees->values(), $request->validated('due_date'));
        $skipped = $ids->count() - $created;

        return redirect()->route('trainings.show', $training)->with('success', trim(
            "{$created} personele eğitim atandı.".($skipped ? " {$skipped} kişi zaten atanmıştı." : '')
        ));
    }

    /**
     * Personel sayfasından: personele seçilen eğitimleri ata.
     */
    public function storeForEmployee(AssignTrainingsRequest $request, int $employee, PersonnelApiClient $personnel, AssignTraining $assign): RedirectResponse
    {
        try {
            $data = $personnel->find($employee, fresh: true);
        } catch (PersonnelApiException) {
            return back()->with('error', PersonnelApiException::USER_MESSAGE);
        }

        abort_if($data === null, 404, 'Personel bulunamadı.');

        $created = 0;
        foreach (Training::findMany($request->validated('training_ids')) as $training) {
            $created += $assign->handle($training, [$data], $request->validated('due_date'));
        }

        return redirect()->route('employees.show', $employee)
            ->with('success', "{$data->fullName()} için {$created} eğitim ataması yapıldı.");
    }

    public function update(Request $request, TrainingAssignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AssignmentStatus::class)],
        ]);

        $assignment->changeStatus(AssignmentStatus::from($data['status']));

        return back()->with('success', "{$assignment->employee_name}: \"{$assignment->training->title}\" durumu {$assignment->status->label()} olarak güncellendi.");
    }

    /**
     * Son tarihi ve tamamlanma tarihini düzenleme (atama satırındaki pencereden).
     */
    public function updateDates(Request $request, TrainingAssignment $assignment): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'due_date' => ['nullable', 'date'],
            'completed_on' => $assignment->status === AssignmentStatus::Completed
                ? ['required', 'date', 'before_or_equal:today']
                : ['prohibited'],
        ]);

        // Form bir pencerede olduğu için hata, sayfanın üstünde bildirim olarak gösterilir
        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first());
        }

        $data = $validator->validated();
        $assignment->changeDates($data['due_date'] ?? null, $data['completed_on'] ?? null);

        return back()->with('success', "{$assignment->employee_name}: \"{$assignment->training->title}\" ataması güncellendi.");
    }

    public function destroy(TrainingAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return back()->with('success', "{$assignment->employee_name} kişisinin ataması kaldırıldı.");
    }
}
