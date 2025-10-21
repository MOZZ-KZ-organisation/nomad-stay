<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array {
        return [
            'city_id' => 'required|integer|exists:cities,id',
            'guests' => 'required|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'nullable|in:hotel,apartment,hostel,other',
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0',
            'stars' => 'nullable|array',
            'stars.*' => 'integer|min:0|max:5',
            'page' => 'nullable|integer|min:1'
        ];
    }
}
