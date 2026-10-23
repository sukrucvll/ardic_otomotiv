<?php

namespace App\Models;

use App\Support\TurkishText;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['first_name', 'last_name', 'email', 'department'])]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Ad soyad.
     */
    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    /**
     * Avatar için baş harfler.
     */
    protected function initials(): Attribute
    {
        return Attribute::get(fn () => mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    /**
     * Departmanın arayüz rengi.
     */
    protected function departmentColor(): Attribute
    {
        return Attribute::get(fn () => config("personnel.departments.{$this->department}", 'secondary'));
    }

    /**
     * Ad soyad veya e-postaya göre Türkçe büyük/küçük harf duyarsız arama.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.TurkishText::lower(trim($term)).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->whereRaw("tr_lower(first_name || ' ' || last_name) like ?", [$term])
                ->orWhereRaw('tr_lower(email) like ?', [$term]);
        });
    }

    /**
     * Ad soyada göre Türkçe alfabe sırası.
     */
    public function scopeOrderByName(Builder $query): Builder
    {
        return $query->orderByRaw('first_name collate turkish')->orderByRaw('last_name collate turkish');
    }

    public function scopeInDepartment(Builder $query, ?string $department): Builder
    {
        return $query->when(filled($department), fn (Builder $query) => $query->where('department', $department));
    }
}
