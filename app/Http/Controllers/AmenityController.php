<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function index()
    {
        return Amenity::select('id', 'name')->orderBy('name')->get();
    }
}