<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => 'required|exists:hotels,id',
            'rating' => 'required|integer|min:1|max:10',
            'comment' => 'nullable|string',
            'media' => 'nullable|array|max:5',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
