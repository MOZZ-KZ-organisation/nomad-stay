<?php

namespace App\Http\Controllers;

use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q');    
        $query = City::with('country');
        if (!empty($search)) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhereHas('country', fn($q) => 
                      $q->where('name', 'like', "%{$search}%")
                  );
        }
        $cities = $query->orderBy('name')->get();
        return CityResource::collection($cities);
    }
}
