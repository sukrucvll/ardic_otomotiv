<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrainingRequest;
use App\Models\Training;
use App\Services\Personnel\PersonnelApiClient;
use App\Services\Personnel\PersonnelApiException;
use App\Support\TurkishText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function index(Request $request): View
    {
        $trainings = Training::withProgress()
            ->when($request->query('search'), fn ($query, $search) => $query->whereRaw('tr_lower(title) like ?', ['%'.TurkishText::lower(trim($search)).'%']))
            ->when($request->query('category'), fn ($query, $category) => $query->where('category', $category))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('trainings.index', [
            'trainings' => $trainings,
            'categories' => config('training.categories'),
        ]);
    }

    public function create(): View
    {
        return view('trainings.create', [
            'training' => new Training(['duration_hours' => 2]),
            'categories' => config('training.categories'),
        ]);
    }

    public function store(TrainingRequest $request): RedirectResponse
    {
        $training = Training::create($request->validated());

        return redirect()->route('trainings.show', $training)
            ->with('success', "\"{$training->title}\" eğitimi oluşturuldu. Şimdi personel atayabilirsiniz.");
    }

    public function show(Training $training, PersonnelApiClient $personnel): View
    {
        $assignments = $training->assignments()->orderByRaw('employee_name collate turkish')->get();

        $employees = collect();
        $departments = collect();
        $personnelUnavailable = false;

        try {
            $employees = $personnel->employees()->sort(fn ($a, $b) => TurkishText::compare($a->fullName(), $b->fullName()));
            $departments = $personnel->departments();
        } catch (PersonnelApiException) {
            $personnelUnavailable = true;
        }

        return view('trainings.show', [
            'training' => $training,
            'assignments' => $assignments,
            'statusCounts' => $assignments->countBy(fn ($assignment) => $assignment->status->value),
            'employees' => $employees,
            'departments' => $departments,
            'assignedIds' => $assignments->pluck('employee_id')->flip(),
            'personnelUnavailable' => $personnelUnavailable,
        ]);
    }

    public function edit(Training $training): View
    {
        return view('trainings.edit', [
            'training' => $training,
            'categories' => config('training.categories'),
        ]);
    }

    public function update(TrainingRequest $request, Training $training): RedirectResponse
    {
        $training->update($request->validated());

        return redirect()->route('trainings.show', $training)
            ->with('success', 'Eğitim bilgileri güncellendi.');
    }

    public function destroy(Training $training): RedirectResponse
    {
        $training->delete();

        return redirect()->route('trainings.index')
            ->with('success', "\"{$training->title}\" eğitimi ve atamaları silindi.");
    }
}
