<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => ['id' => $this->user->id, 'name' => $this->user->name],
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at->format('d.m.Y H:i'),
            'media' => $this->media?->map(fn($m) => url(Storage::url($m->path))) ?? [],
        ];
    }
}
