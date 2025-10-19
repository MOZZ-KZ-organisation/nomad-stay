<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => 'required|exists:hotels,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'guests' => 'nullable|integer|min:1'
        ];
    }
}
