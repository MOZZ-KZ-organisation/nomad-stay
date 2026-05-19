<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use TCG\Voyager\Models\Role;
use TCG\Voyager\Models\User;

class ManagerAuthController extends Controller
{
    public function showRegister()
    {
        return view('manager.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users',
            'password'       => 'required|confirmed|min:8',
            'hotel_title'    => 'required|string|max:255',
            'hotel_address'  => 'nullable|string|max:500',
        ]);
        $managerRole = Role::where('name', 'hotel_manager')->firstOrFail();
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => bcrypt($data['password']),
            'role_id'  => $managerRole->id,
        ]);
        Hotel::create([
            'manager_id' => $user->id,
            'title'      => $data['hotel_title'],
            'address'    => $data['hotel_address'] ?? '',
            'slug'       => \Str::slug($data['hotel_title']),
            'is_active'  => false, 
        ]);
        auth()->login($user);
        return redirect()->route('manager.calendar');
    }
}
