<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login'       => 'sometimes|string|max:50|unique:users,login,' . $this->user()->id,
            'name'        => 'nullable|string|max:255',
            'phone'       => 'nullable|string|max:50',
            'birth_date'  => 'nullable|date',
            'citizenship' => 'nullable|string|max:100',
            'address'     => 'nullable|string|max:100',
            'email'       => 'nullable|email|unique:users,email,' . $this->user()->id,
            'avatar'      => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
