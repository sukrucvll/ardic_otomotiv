<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unique = Rule::unique('employees')->ignore($this->route('employee'));

        // Yeni kayıtta silinmiş personelin e-postası kabul edilir; o kayıt geri yüklenir
        // (EmployeeController::store). Güncellemede silinmiş kayıtlar da dahil tekil kalmalı.
        if (! $this->route('employee')) {
            $unique->withoutTrashed();
        }

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', $unique],
            'department' => ['required', 'string', Rule::in(array_keys(config('personnel.departments')))],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Yalnızca metin değerler düzenlenir; dizi gibi yanlış türler doğrulamada reddedilir
        foreach (['first_name', 'last_name', 'email'] as $field) {
            if (is_string($value = $this->input($field))) {
                $this->merge([$field => $field === 'email' ? mb_strtolower(trim($value)) : trim($value)]);
            }
        }
    }
}
