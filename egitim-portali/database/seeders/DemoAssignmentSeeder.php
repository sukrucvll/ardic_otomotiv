<?php

namespace Database\Seeders;

use App\Actions\AssignTraining;
use App\Enums\AssignmentStatus;
use App\Models\Training;
use App\Models\TrainingAssignment;
use App\Services\Personnel\PersonnelApiClient;
use App\Services\Personnel\PersonnelApiException;
use Illuminate\Database\Seeder;

/**
 * Personel API'sinden alınan personele örnek atamalar yapar.
 * API'ye ulaşılamazsa sessizce atlanır (atamalar arayüzden de yapılabilir).
 */
class DemoAssignmentSeeder extends Seeder
{
    public function run(PersonnelApiClient $personnel, AssignTraining $assign): void
    {
        try {
            $employees = $personnel->employees();
        } catch (PersonnelApiException $e) {
            $this->command?->warn("Demo atamalar atlandı: {$e->getMessage()}");

            return;
        }

        if ($employees->isEmpty()) {
            return;
        }

        $lastId = (int) TrainingAssignment::max('id');

        // Zorunlu eğitimler herkese, diğerleri rastgele birkaç kişiye
        foreach (Training::all() as $training) {
            $targets = $training->is_mandatory
                ? $employees
                : $employees->random(min(6, $employees->count()));

            $assign->handle($training, $targets, now()->addDays(random_int(-10, 45))->toDateString());
        }

        // Yalnızca bu çalıştırmada oluşan atamaların durumlarını çeşitlendir;
        // daha önce yapılmış atamalar ve durumları olduğu gibi kalır
        $created = TrainingAssignment::where('id', '>', $lastId)->get();

        $created->each(function (TrainingAssignment $assignment) {
            $assignment->changeStatus(match (random_int(1, 10)) {
                1, 2, 3, 4, 5 => AssignmentStatus::Completed,
                6, 7 => AssignmentStatus::InProgress,
                default => AssignmentStatus::Assigned,
            });
        });

        $this->command?->info($created->count().' demo atama oluşturuldu.');
    }
}
