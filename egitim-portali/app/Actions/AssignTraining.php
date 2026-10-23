<?php

namespace App\Actions;

use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Services\Personnel\EmployeeData;
use Illuminate\Support\Facades\DB;

class AssignTraining
{
    /**
     * Eğitimi verilen personele atar. Zaten atanmış olanlar atlanır.
     *
     * @param  iterable<EmployeeData>  $employees
     * @return int Yeni oluşturulan atama sayısı
     */
    public function handle(Training $training, iterable $employees, ?string $dueDate = null): int
    {
        return DB::transaction(function () use ($training, $employees, $dueDate) {
            $created = 0;

            foreach ($employees as $employee) {
                $assignment = TrainingAssignment::firstOrCreate(
                    ['training_id' => $training->id, 'employee_id' => $employee->id],
                    [
                        'employee_name' => $employee->fullName(),
                        'employee_email' => $employee->email,
                        'employee_department' => $employee->department,
                        'due_date' => $dueDate,
                    ],
                );

                $created += (int) $assignment->wasRecentlyCreated;
            }

            return $created;
        });
    }
}
