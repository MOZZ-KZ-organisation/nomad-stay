<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Booking::addGlobalScope('manager_scope', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (!app()->runningInConsole() && request()->is('admin/*')) {
                $user = auth()->user();
                if ($user && $user->isHotelManager()) {
                    $hotelId = $user->managedHotel?->id;
                    $builder->whereIn('room_id', function ($query) use ($hotelId) {
                        $query->select('id')
                            ->from('rooms')
                            ->where('hotel_id', $hotelId);
                    });
                }
            }
        });
        \App\Models\Review::addGlobalScope('manager_scope', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (!app()->runningInConsole() && request()->is('admin/*')) {
                $user = auth()->user();
                if ($user && $user->isHotelManager()) {
                    $builder->where('hotel_id', $user->managedHotel?->id);
                }
            }
        });
    }
}
