<?php

namespace App\Actions;

use App\Models\TrainingAssignment;
use App\Services\Personnel\EmployeeData;

/**
 * Atamalardaki personel snapshot'larını (ad, e-posta, departman)
 * Personel Sistemi'ndeki güncel bilgiyle eşitler.
 */
class SyncEmployeeSnapshots
{
    /**
     * @param  iterable<EmployeeData>  $employees
     * @return int Güncellenen atama sayısı
     */
    public function handle(iterable $employees): int
    {
        $updated = 0;

        foreach ($employees as $employee) {
            $updated += TrainingAssignment::where('employee_id', $employee->id)
                ->where(function ($query) use ($employee) {
                    $query->where('employee_name', '!=', $employee->fullName())
                        ->orWhere('employee_email', '!=', $employee->email)
                        ->orWhere('employee_department', '!=', $employee->department);
                })
                ->update([
                    'employee_name' => $employee->fullName(),
                    'employee_email' => $employee->email,
                    'employee_department' => $employee->department,
                ]);
        }

        return $updated;
    }
}
