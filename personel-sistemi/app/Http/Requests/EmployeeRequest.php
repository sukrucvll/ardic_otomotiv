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
            'email' => ['required', 'email', 'max:255', $unique],
            'department' => ['required', Rule::in(array_keys(config('personnel.departments')))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => trim((string) $this->input('first_name')),
            'last_name' => trim((string) $this->input('last_name')),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
