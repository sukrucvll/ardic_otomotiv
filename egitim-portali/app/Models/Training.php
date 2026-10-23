<?php

namespace App\Models;

use App\Enums\AssignmentStatus;
use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'category', 'description', 'duration_hours', 'is_mandatory'])]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_hours' => 'integer',
            'is_mandatory' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TrainingAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    /**
     * Atama ve tamamlanma sayılarını yükler (assignments_count, completed_count).
     */
    public function scopeWithProgress(Builder $query): Builder
    {
        return $query->withCount([
            'assignments',
            'assignments as completed_count' => fn (Builder $query) => $query->where('status', AssignmentStatus::Completed),
        ]);
    }

    protected function completionRate(): Attribute
    {
        return Attribute::get(fn () => $this->assignments_count
            ? (int) round($this->completed_count * 100 / $this->assignments_count)
            : 0);
    }

    protected function categoryColor(): Attribute
    {
        return Attribute::get(fn () => config("training.categories.{$this->category}.0", 'secondary'));
    }

    protected function categoryIcon(): Attribute
    {
        return Attribute::get(fn () => config("training.categories.{$this->category}.1", 'mortarboard'));
    }
}
