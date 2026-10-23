<?php

namespace App\Listeners;

use App\Actions\SyncEmployeeSnapshots;
use App\Events\PersonnelListFetched;

/**
 * Personel Sistemi'nde adı, e-postası veya departmanı değişen kişilerin atamalardaki
 * bilgileri, güncel liste alınır alınmaz düzeltilir. Böylece panel ve filtreler
 * eski departman bilgisiyle hesaplanmaz.
 */
class SyncSnapshotsFromPersonnelList
{
    public function __construct(private readonly SyncEmployeeSnapshots $sync) {}

    public function handle(PersonnelListFetched $event): void
    {
        $this->sync->handle($event->employees);
    }
}
