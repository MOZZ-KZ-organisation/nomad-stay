<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'login'       => $this->login,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'birth_date'  => $this->birth_date?->format('Y-m-d'),
            'citizenship' => $this->citizenship,
            'address'     => $this->address,
            'email'       => $this->email,
            'avatar'      => $this->avatar ? url(Storage::url($this->avatar)): null,
        ];
    }
}
