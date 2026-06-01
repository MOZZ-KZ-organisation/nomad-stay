<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends \TCG\Voyager\Models\User
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;
    
    protected $fillable = [
        'login',
        'name',
        'phone',
        'birth_date',
        'citizenship',
        'address',
        'email',
        'avatar',
        'password',
        'role_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
        ];
    }

    public function managedHotel()
    {
        return $this->hasOne(Hotel::class, 'manager_id');
    }

    public function isHotelManager(): bool
    {
        return $this->role_id === \TCG\Voyager\Models\Role::where('name', 'hotel_manager')->value('id');
    }

    public function isAdmin(): bool
    {
        return $this->role_id === \TCG\Voyager\Models\Role::where('name', 'admin')->value('id');
    }

    public function favorites()
    {
        return $this->belongsToMany(Hotel::class, 'favorites', 'user_id', 'hotel_id')
            ->withPivot(['start_date', 'end_date', 'guests'])
            ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
