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
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'guests'     => ['nullable', 'integer', 'min:1'],
        ];
    }
}
