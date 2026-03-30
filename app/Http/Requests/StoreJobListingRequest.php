<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(['full-time', 'part-time', 'contract', 'internship'])],
            'location' => 'required|string|max:255',
            'salary_range' => 'nullable|string|max:100',
            'description' => 'required|string',
            'slots' => 'required|integer|min:1',
            'status' => ['sometimes', Rule::in(['open', 'closed', 'draft'])],
            'posted_date' => 'nullable|date',
            'deadline' => 'nullable|date|after:posted_date',
            'skills' => 'nullable|array',
            'skills.*' => 'string|max:100',
        ];
    }
}
