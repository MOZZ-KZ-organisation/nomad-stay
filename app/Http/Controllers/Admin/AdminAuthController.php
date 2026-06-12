<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Models\Role;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isHotelManager()) {
            Auth::logout();
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }
        $token = $user->createToken('admin_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role?->name,
            ],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|confirmed|min:8',
            'hotel_title'   => 'required|string|max:255',
            'hotel_address' => 'nullable|string|max:500',
            'hotel_email'   => 'nullable|email|max:255',
        ], [
            'name.required'        => 'Введите ваше имя',
            'email.required'       => 'Введите email',
            'email.email'          => 'Введите корректный email',
            'email.unique'         => 'Этот email уже зарегистрирован',
            'password.required'    => 'Введите пароль',
            'password.confirmed'   => 'Пароли не совпадают',
            'password.min'         => 'Пароль должен содержать минимум 8 символов',
            'hotel_title.required' => 'Введите название отеля',
            'hotel_email.email'    => 'Введите корректный email отеля',
        ]);
        $managerRole = Role::where('name', 'hotel_manager')->firstOrFail();
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
            'role_id'  => $managerRole->id,
        ]);
        $hotel = Hotel::create([
            'manager_id'       => $user->id,
            'title'            => $request->hotel_title,
            'slug'             => Str::slug($request->hotel_title),
            'address'          => $request->hotel_address ?? null,
            'email'            => $request->hotel_email ?? null,
            'stars'            => 0,
            'is_active'        => false,
            'cancellation_fee' => 0,
        ]);
        $token = $user->createToken('admin_panel')->plainTextToken;
        return response()->json([
            'message' => 'Регистрация успешна. Ваш отель будет активирован после проверки администратором.',
            'token'   => $token,
            'user'    => $this->formatUser($user),
            'hotel'   => [
                'id'        => $hotel->id,
                'title'     => $hotel->title,
                'address'   => $hotel->address,
                'email'     => $hotel->email,
                'is_active' => $hotel->is_active,
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Выход выполнен']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('role');
        return response()->json(['user' => $this->formatUser($user)]);
    }
 
    protected function formatUser($user): array
    {
        $isManager = $user->isHotelManager();
        $hotel     = $isManager ? $user->managedHotel : null;
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role?->name,
            'hotel' => $hotel ? [
                'id'        => $hotel->id,
                'title'     => $hotel->title,
                'is_active' => $hotel->is_active,
            ] : null,
        ];
    }
}