<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainingRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(config('training.categories')))],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:1000'],
            'is_mandatory' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_mandatory' => $this->boolean('is_mandatory')]);
    }
}
