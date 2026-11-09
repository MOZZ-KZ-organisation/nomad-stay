<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'hotel_id',
        'room_id',
        'booking_number',
        'start_date',
        'end_date',
        'guests',
        'price_for_period',
        'tax',
        'total_price',
        'status',
        'is_paid',
        'type',
        'source',
        'first_name',
        'last_name',
        'email',
        'country',
        'phone',
        'is_business_trip',
        'special_requests',
        'arrival_time',
        // --- новые поля ---
        'planned_check_in_at',
        'planned_check_out_at',
        'actual_check_in_at',
        'actual_check_out_at',
        'early_check_in_amount',
        'late_check_out_amount',
        'services_amount',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'is_business_trip' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'arrival_time' => 'datetime:H:i',
        'planned_check_in_at' => 'datetime',
        'planned_check_out_at' => 'datetime',
        'actual_check_in_at' => 'datetime',
        'actual_check_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'hotel_id', 'hotel_id')
            ->where('user_id', $this->user_id);
    }

    /**
     * История переселений гостя по номерам. Текущий "активный" сегмент —
     * тот, у которого check_out_at = null (или последний по check_in_at).
     */
    public function roomSegments()
    {
        return $this->hasMany(BookingRoomSegment::class)->orderBy('check_in_at');
    }

    public function services()
    {
        return $this->hasMany(BookingService::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getColorAttribute()
    {
        return match ($this->status) {
            'checked_in'  => '#a9d445', 
            'booked'      => '#ffd97f', 
            'checked_out' => '#b5b7b9', 
            'cancelled'   => '#EB5757',
            default       => '#E5E7EB',
        };
    }

    /**
     * Сколько фактически оплачено (сумма завершённых платежей).
     */
    public function getPaidAmountAttribute(): float
    {
        // Список/календарь: сумма уже подгружена одним подзапросом (см. scopeWithPaidSum) — без N+1.
        if (array_key_exists('paid_sum', $this->attributes)) {
            return (float) $this->attributes['paid_sum'];
        }

        // Платежи уже загружены (карточка брони) — считаем в памяти.
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->where('status', 'completed')->sum('amount');
        }

        return (float) $this->payments()->where('status', 'completed')->sum('amount');
    }

    /**
     * Остаток к оплате = итоговая сумма брони - оплачено.
     */
    public function getBalanceDueAttribute(): float
    {
        return round((float) $this->total_price - $this->paid_amount, 2);
    }

    /**
     * Статус оплаты: unpaid / prepaid / paid. Вычисляется по платежам, в БД не хранится.
     */
    public function getPaymentStatusAttribute(): string
    {
        return PaymentStatus::resolve((float) $this->total_price, $this->paid_amount)->value;
    }

    /**
     * Подгружает сумму завершённых платежей одним подзапросом (атрибут paid_sum),
     * чтобы paid_amount / balance_due / payment_status не делали запрос на каждую бронь.
     */
    public function scopeWithPaidSum($query)
    {
        return $query->withSum(
            ['payments as paid_sum' => fn ($q) => $q->where('status', 'completed')],
            'amount'
        );
    }

    public function scopeOverlapping($query, $start, $end)
    {
        return $query
            ->where('end_date', '>', $start)
            ->where('start_date', '<', $end);
    }

    public function scopeForCalendar($query, $start, $end)
    {
        return $query->whereBetween('start_date', [$start, $end])
            ->orWhereBetween('end_date', [$start, $end])
            ->orWhere(function ($q) use ($start, $end) {
                $q->where('start_date', '<', $start)
                  ->where('end_date', '>', $end);
            });
    }

    public function getPricePerNightAttribute()
    {
        $nights = $this->start_date->diffInDays($this->end_date);
        return $nights > 0
            ? $this->total_price / $nights
            : $this->total_price;
    }

    public static function generateBookingNumber(): string
    {
        do {
            $year = date('Y');
            $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $bookingNumber = "NS-{$year}-{$random}";
        } while (self::where('booking_number', $bookingNumber)->exists());
 
        return $bookingNumber;
    }

    protected static function booted(): void
    {
        static::creating(function ($booking) {
            $booking->booking_number = self::generateBookingNumber();
            $room = Room::find($booking->room_id);
            if (!$room) {
                return;
            }
            $booking->hotel_id = $room->hotel_id;
            $nights = Carbon::parse($booking->start_date)
                ->diffInDays(Carbon::parse($booking->end_date));
            $basePrice = $room->price * $nights;
            $tax = $basePrice * env('BOOKING_TAX_RATE', 0);
            $booking->price_for_period = $basePrice;
            $booking->tax = $tax;
            $booking->total_price = $basePrice + $tax
                + ($booking->early_check_in_amount ?? 0)
                + ($booking->late_check_out_amount ?? 0)
                + ($booking->services_amount ?? 0);
        });
        static::updating(function ($booking) {
            if (
                $booking->isDirty('room_id') ||
                $booking->isDirty('start_date') ||
                $booking->isDirty('end_date')
            ) {
                $room = Room::find($booking->room_id);
                if (!$room) {
                    return;
                }
                $booking->hotel_id = $room->hotel_id;
                $nights = Carbon::parse($booking->start_date)
                    ->diffInDays(Carbon::parse($booking->end_date));
                $basePrice = $room->price * $nights;
                $tax = $basePrice * env('BOOKING_TAX_RATE', 0);
                $booking->price_for_period = $basePrice;
                $booking->tax = $tax;
                $booking->total_price = $basePrice + $tax
                    + ($booking->early_check_in_amount ?? 0)
                    + ($booking->late_check_out_amount ?? 0)
                    + ($booking->services_amount ?? 0);
            }
        });
        static::saving(function ($booking) {

            $request = request();
            if ($request->has('is_paid')) {
                $booking->is_paid = (int) $request->input('is_paid');
            }
            if ($request->has('is_business_trip')) {
                $booking->is_business_trip = (int) $request->input('is_business_trip');
            }
        });
    }
}