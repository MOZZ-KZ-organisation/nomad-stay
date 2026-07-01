<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\City;
use App\Models\CityAttraction;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminContentController extends Controller
{
    // ======================== ГОРОДА ========================

    /**
     * GET /admin-api/cities
     */
    public function cities(Request $request)
    {
        $cities = City::with('country')->withCount('hotels')->get();
        return response()->json([
            'data' => $cities->map(fn($c) => [
                'id'           => $c->id,
                'name'         => $c->name,
                'country_id'   => $c->country_id,
                'country'      => $c->country?->name,
                'image'        => $c->image ? url(Storage::url($c->image)) : null,
                'hotels_count' => $c->hotels_count,
            ]),
        ]);
    }

    /**
     * POST /admin-api/cities
     */
    public function storeCity(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'image'      => 'nullable|image|mimes:jpeg,png,webp|max:3072',
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('cities', 'public');
        }
        $city = City::create($data);
        return response()->json(['message' => 'Город создан', 'data' => $city], 201);
    }

    /**
     * PATCH /admin-api/cities/{id}
     */
    public function updateCity(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $city = City::findOrFail($id);
        $data = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'country_id' => 'sometimes|exists:countries,id',
            'image'      => 'nullable|image|mimes:jpeg,png,webp|max:3072',
        ]);
        if ($request->hasFile('image')) {
            if ($city->image && Storage::disk('public')->exists($city->image)) {
                Storage::disk('public')->delete($city->image);
            }
            $data['image'] = $request->file('image')->store('cities', 'public');
        }
        $city->update($data);
        return response()->json(['message' => 'Город обновлён', 'data' => $city]);
    }

    /**
     * DELETE /admin-api/cities/{id}
     */
    public function destroyCity(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        City::findOrFail($id)->delete();
        return response()->json(['message' => 'Город удалён']);
    }

    // ======================== СТРАНЫ ========================

    /**
     * GET /admin-api/countries
     */
    public function countries()
    {
        return response()->json([
            'data' => Country::withCount('cities')->get()->map(fn($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'cities_count'=> $c->cities_count,
            ]),
        ]);
    }

    /**
     * POST /admin-api/countries
     */
    public function storeCountry(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $data = $request->validate(['name' => 'required|string|max:255|unique:countries,name']);
        $country = Country::create($data);
        return response()->json(['message' => 'Страна создана', 'data' => $country], 201);
    }

    /**
     * PATCH /admin-api/countries/{id}
     */
    public function updateCountry(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $country = Country::findOrFail($id);
        $data    = $request->validate(['name' => 'required|string|max:255|unique:countries,name,' . $id]);
        $country->update($data);
        return response()->json(['message' => 'Страна обновлена', 'data' => $country]);
    }

    /**
     * DELETE /admin-api/countries/{id}
     */
    public function destroyCountry(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        Country::findOrFail($id)->delete();
        return response()->json(['message' => 'Страна удалена']);
    }

    // ======================== УДОБСТВА ========================

    /**
     * GET /admin-api/amenities
     */
    public function amenities()
    {
        return response()->json([
            'data' => Amenity::withCount('hotels')->get()->map(fn($a) => [
                'id'           => $a->id,
                'code'         => $a->code,
                'name'         => $a->name,
                'hotels_count' => $a->hotels_count,
            ]),
        ]);
    }

    /**
     * POST /admin-api/amenities
     */
    public function storeAmenity(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $data    = $request->validate([
            'code' => 'required|string|max:50|unique:amenities,code',
            'name' => 'required|string|max:255',
        ]);
        $amenity = Amenity::create($data);
        return response()->json(['message' => 'Удобство создано', 'data' => $amenity], 201);
    }

    /**
     * PATCH /admin-api/amenities/{id}
     */
    public function updateAmenity(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $amenity = Amenity::findOrFail($id);
        $data    = $request->validate([
            'code' => 'sometimes|string|max:50|unique:amenities,code,' . $id,
            'name' => 'sometimes|string|max:255',
        ]);
        $amenity->update($data);
        return response()->json(['message' => 'Удобство обновлено', 'data' => $amenity]);
    }

    /**
     * DELETE /admin-api/amenities/{id}
     */
    public function destroyAmenity(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        Amenity::findOrFail($id)->delete();
        return response()->json(['message' => 'Удобство удалено']);
    }

    // ======================== ДОСТОПРИМЕЧАТЕЛЬНОСТИ ========================

    /**
     * GET /admin-api/city-attractions
     */
    public function attractions(Request $request)
    {
        $query = CityAttraction::with('city:id,name');
        if ($request->filled('city_id')) {
            $query->where('city_id', $request->city_id);
        }
        return response()->json([
            'data' => $query->get()->map(fn($a) => [
                'id'      => $a->id,
                'city_id' => $a->city_id,
                'city'    => $a->city?->name,
                'title'    => $a->title,
                'image'   => $a->image_path ? url(Storage::url($a->image_path)) : null,
            ]),
        ]);
    }

    /**
     * POST /admin-api/city-attractions
     */
    public function storeAttraction(Request $request)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        $data = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'title'    => 'required|string|max:255',
            'image'   => 'nullable|image|mimes:jpeg,png,webp|max:3072',
        ]);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('attractions', 'public');
        }
        $attraction = CityAttraction::create($data);
        return response()->json(['message' => 'Достопримечательность добавлена', 'data' => $attraction], 201);
    }

    /**
     * DELETE /admin-api/city-attractions/{id}
     */
    public function destroyAttraction(Request $request, $id)
    {
        abort_if(!$request->user()->isAdmin(), 403);
        CityAttraction::findOrFail($id)->delete();
        return response()->json(['message' => 'Достопримечательность удалена']);
    }
}