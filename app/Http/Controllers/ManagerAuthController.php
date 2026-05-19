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
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|confirmed|min:8',
            'hotel_title'   => 'required|string|max:255',
            'hotel_address' => 'nullable|string|max:500',
        ], [
            'name.required'        => 'Введите ваше имя',
            'email.required'       => 'Введите email',
            'email.email'          => 'Введите корректный email',
            'email.unique'         => 'Этот email уже зарегистрирован',
            'password.required'    => 'Введите пароль',
            'password.confirmed'   => 'Пароли не совпадают',
            'password.min'         => 'Пароль должен содержать минимум 8 символов',
            'hotel_title.required' => 'Введите название отеля',
        ]);

        $managerRole = Role::where('name', 'hotel_manager')->firstOrFail();

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
            'role_id'  => $managerRole->id,
        ]);

        Hotel::create([
            'manager_id' => $user->id,
            'title'      => $request->hotel_title,
            'slug'       => \Str::slug($request->hotel_title),
            'address'    => $request->hotel_address ?? null,
            'stars'      => 0,
            'is_active'  => false,
            'cancellation_fee' => 0,
        ]);

        auth()->login($user);

        return redirect()->route('manager.calendar');
    }
}
