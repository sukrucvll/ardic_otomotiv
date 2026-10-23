<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Bir personele bir veya daha fazla eğitim atama.
 */
class AssignTrainingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'training_ids' => ['required', 'array', 'min:1'],
            'training_ids.*' => ['integer', 'distinct', 'exists:trainings,id'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'training_ids.required' => 'En az bir eğitim seçmelisiniz.',
        ];
    }
}
