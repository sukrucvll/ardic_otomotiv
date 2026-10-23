<?php

namespace App\Events;

use App\Services\Personnel\EmployeeData;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Personel listesi API'den yeni alındığında (önbellekten değil) yayınlanır.
 */
class PersonnelListFetched
{
    use Dispatchable;

    /**
     * @param  Collection<int, EmployeeData>  $employees
     */
    public function __construct(public readonly Collection $employees) {}
}
