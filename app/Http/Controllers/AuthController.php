<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthorizedResetPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendOtpRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\VerifyLoginRequest;
use App\Http\Resources\UserResource;
use App\Mail\OtpMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token'   => $token], 201);
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Неверный email или пароль'
            ], 401);
        }
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token'   => $token]);
    }

    public function sendCode(SendOtpRequest $request)
    {
        $code = random_int(10000, 99999);
        $email = $request->email;
        EmailVerificationCode::updateOrCreate(
            ['email' => $email],
            [
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10),
            ]
        );
        Mail::to($request->email)->queue(new OtpMail($code));
        return response()->json(['message' => 'Код отправлен на вашу почту.']);
    }

    public function verifyCode(VerifyLoginRequest $request)
    {
        $verification = EmailVerificationCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', Carbon::now())
            ->first();
        if (!$verification) {
            return response()->json(['message' => 'Неверный код или код истек.'], 400);
        }
        $verification->delete();
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'NOT_FOUND'], 404);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    public function resetPassword(AuthorizedResetPasswordRequest $request)
    {
        $user = $request->user(); 
        $user->password = Hash::make($request->password);
        $user->save();
        return response()->json(['message' => 'CHANGED'], 200);
    }

    public function profile()
    {
        return new UserResource(Auth::user());
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        if (!($user instanceof User)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $data = $request->validated();
        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        $user->update($data);
        return new UserResource($user);
    }
}
