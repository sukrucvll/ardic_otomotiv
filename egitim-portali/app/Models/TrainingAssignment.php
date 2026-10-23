<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use App\Support\TurkishText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'training_id', 'employee_id', 'employee_name', 'employee_email', 'employee_department',
    'status', 'due_date', 'completed_at',
])]
class TrainingAssignment extends Model
{
    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'assigned',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Training, $this>
     */
    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Durumu değiştirir; tamamlandığında tamamlanma zamanını kaydeder.
     */
    public function changeStatus(AssignmentStatus $status): void
    {
        $this->status = $status;
        $this->completed_at = $status === AssignmentStatus::Completed
            ? ($this->completed_at ?? now())
            : null;
        $this->save();
    }

    /**
     * Personel adı veya e-postasına göre Türkçe büyük/küçük harf duyarsız arama.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.TurkishText::lower(trim($term)).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->whereRaw('tr_lower(employee_name) like ?', [$term])
                ->orWhereRaw('tr_lower(employee_email) like ?', [$term]);
        });
    }

    /**
     * Son tarihi geçmiş ve tamamlanmamış atamalar.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', AssignmentStatus::Completed)
            ->whereDate('due_date', '<', today());
    }

    protected function isOverdue(): Attribute
    {
        return Attribute::get(fn () => $this->status !== AssignmentStatus::Completed
            && $this->due_date?->lt(today()));
    }

    protected function employeeInitials(): Attribute
    {
        return Attribute::get(function () {
            $parts = preg_split('/\s+/u', trim($this->employee_name));

            return mb_substr($parts[0], 0, 1).mb_substr(end($parts), 0, 1);
        });
    }
}
