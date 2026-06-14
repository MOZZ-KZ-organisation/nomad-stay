<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminHotelController extends Controller
{
    public function show(Request $request)
    {
        $hotel = $this->getManagerHotel($request);
        $hotel->load([
            'city.country',
            'amenities',
            'images',
            'nearby',
            'discount',
            'rooms.images',
        ]);
        return response()->json(['data' => $this->formatHotel($hotel)]);
    }

    public function update(Request $request)
    {
        $hotel = $this->getManagerHotel($request);
        $data = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'address'          => 'nullable|string|max:500',
            'email'            => 'nullable|email|max:255',
            'city_id'          => 'sometimes|exists:cities,id',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'stars'            => 'sometimes|integer|min:0|max:5',
            'type'             => 'nullable|string',
            'cancellation_fee' => 'nullable|integer|min:0',
            'amenity_ids'      => 'nullable|array',
            'amenity_ids.*'    => 'exists:amenities,id',
        ]);
        $hotel->update(array_diff_key($data, ['amenity_ids' => true]));
        if ($request->has('amenity_ids')) {
            $hotel->amenities()->sync($data['amenity_ids'] ?? []);
        }
        return response()->json([
            'message' => 'Отель обновлён',
            'data'    => $this->formatHotel(
                $hotel->fresh(['city.country', 'amenities', 'images', 'nearby', 'discount', 'rooms.images'])
            ),
        ]);
    }

    public function uploadImages(Request $request)
    {
        $hotel = $this->getManagerHotel($request);
        $request->validate([
            'images'     => 'required|array|min:1',
            'images.*'   => 'image|mimes:jpeg,png,webp|max:5120',
            'main_index' => 'nullable|integer',
        ]);
        $mainIndex = $request->get('main_index', 0);
        $uploaded  = [];
        foreach ($request->file('images') as $idx => $file) {
            $path  = $file->store('hotels', 'public');
            $image = $hotel->images()->create([
                'path'    => $path,
                'is_main' => ($idx === (int) $mainIndex),
            ]);
            $uploaded[] = [
                'id'      => $image->id,
                'url'     => url(Storage::url($path)),
                'is_main' => $image->is_main,
            ];
        }
        return response()->json(['message' => 'Фото загружены', 'data' => $uploaded], 201);
    }

    public function deleteImage(Request $request, $imageId)
    {
        $hotel = $this->getManagerHotel($request);
        $image = $hotel->images()->findOrFail($imageId);
        if ($image->path && Storage::disk('public')->exists($image->path)) {
            Storage::disk('public')->delete($image->path);
        }
        $image->delete();
        return response()->json(['message' => 'Фото удалено']);
    }

    public function updateNearby(Request $request)
    {
        $hotel = $this->getManagerHotel($request);
        $data = $request->validate([
            'metro'   => 'nullable|string|max:255',
            'station' => 'nullable|string|max:255',
            'park'    => 'nullable|string|max:255',
            'airport' => 'nullable|string|max:255',
        ]);
        $hotel->nearby()->updateOrCreate(['hotel_id' => $hotel->id], $data);
        return response()->json(['message' => 'Расположение обновлено', 'data' => $data]);
    }

    public function updateDiscount(Request $request)
    {
        $hotel = $this->getManagerHotel($request);
        $data = $request->validate([
            'discount_percent' => 'required|integer|min:0|max:100',
        ]);
        $hotel->discount()->updateOrCreate(
            ['hotel_id' => $hotel->id],
            ['discount_percent' => $data['discount_percent']]
        );
        return response()->json([
            'message' => 'Скидка обновлена',
            'data'    => $hotel->discount()->first(),
        ]);
    }

    protected function getManagerHotel(Request $request): Hotel
    {
        $hotel = $request->user()->managedHotel;
        abort_if(!$hotel, 404, 'Отель не найден');
        return $hotel;
    }

    protected function formatHotel(Hotel $h): array
    {
        return [
            'id'               => $h->id,
            'title'            => $h->title,
            'slug'             => $h->slug,
            'description'      => $h->description,
            'address'          => $h->address,
            'email'            => $h->email,
            'city_id'          => $h->city_id,
            'city'             => $h->city?->name,
            'country'          => $h->city?->country?->name,
            'latitude'         => $h->latitude,
            'longitude'        => $h->longitude,
            'stars'            => $h->stars,
            'type'             => $h->type,
            'is_active'        => $h->is_active,
            'min_price'        => $h->min_price,
            'cancellation_fee' => $h->cancellation_fee,
            'discount'         => $h->discount ? [
                'percent'        => $h->discount->discount_percent,
                'price_override' => $h->discount->price_override,
            ] : null,
            'amenities' => $h->amenities?->map(fn($a) => [
                'id'   => $a->id,
                'code' => $a->code,
                'name' => $a->name,
            ]),
            'images' => $h->images?->map(fn($i) => [
                'id'      => $i->id,
                'url'     => url(Storage::url($i->path)),
                'is_main' => $i->is_main,
            ]),
            'nearby' => $h->nearby ? [
                'metro'   => $h->nearby->metro,
                'station' => $h->nearby->station,
                'park'    => $h->nearby->park,
                'airport' => $h->nearby->airport,
            ] : null,
            'rooms' => $h->rooms?->map(fn($r) => [
                'id'        => $r->id,
                'title'     => $r->title,
                'price'     => $r->price,
                'capacity'  => $r->capacity,
                'beds'      => $r->beds,
                'bathrooms' => $r->bathrooms,
                'stock'     => $r->stock,
                'images'    => $r->images?->map(fn($i) => [
                    'id'      => $i->id,
                    'url'     => url(Storage::url($i->path)),
                    'is_main' => $i->is_main,
                ]),
            ]),
            'created_at' => $h->created_at?->format('d.m.Y'),
        ];
    }
}