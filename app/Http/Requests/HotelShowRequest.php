<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HotelShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'before:end_date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'guests'     => ['nullable', 'integer', 'min:1'],
        ];
    }
}
