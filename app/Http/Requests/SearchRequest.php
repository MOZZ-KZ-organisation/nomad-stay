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
            'q' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'guests' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'flexibility' => 'nullable|integer|in:0,1,2', // дни запаса в обе стороны
            'type' => 'nullable|in:hotel,apartment,hostel,other',
            'price_min' => 'nullable|integer|min:0',
            'price_max' => 'nullable|integer|min:0',
            'stars' => 'nullable|array',
            'stars.*' => 'integer|min:0|max:5',
            'page' => 'nullable|integer|min:1'
        ];
    }
}
