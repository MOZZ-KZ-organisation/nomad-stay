<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Models\Role;

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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Выход выполнен']);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
            'role'  => $user->role?->name,
            'hotel' => $user->isHotelManager() ? [
                'id'    => $user->managedHotel?->id,
                'title' => $user->managedHotel?->title,
            ] : null,
        ]);
    }
}