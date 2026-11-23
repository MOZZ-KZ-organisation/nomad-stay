<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Services\BookingPriceCalculator;
use App\Services\NightlyPricing;
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
        // Снимок посуточной расшифровки проживания (см. NightlyPricing)
        'price_breakdown' => 'array',
    ];

    /**
     * Не пересчитывать цену в модельных хуках: стоимость уже посчитана
     * вызывающим кодом через BookingPriceCalculator::applyToBooking().
     * Сбрасывается после сохранения.
     */
    public bool $skipAutoPricing = false;

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

    /**
     * Посуточная расшифровка стоимости проживания, как она была посчитана при
     * создании/изменении брони. У броней, созданных до появления тарифов по датам,
     * снимка нет — тогда она собирается из price_for_period.
     */
    public function priceBreakdownLines(): array
    {
        $lines = $this->price_breakdown;

        return !empty($lines)
            ? $lines
            : NightlyPricing::legacyLines(
                $this->start_date?->toDateString(),
                $this->end_date?->toDateString(),
                $this->price_for_period
            );
    }

    /**
     * Ночи, цена которых уже зафиксирована в сохранённой брони (дата => цена).
     * При изменении дат эти ночи не пересчитываются по текущим тарифам — иначе
     * правка тарифа задним числом меняла бы цену уже созданной брони.
     * Берётся состояние из БД, а не текущие несохранённые значения экземпляра.
     */
    public function lockedNights(): array
    {
        $raw = $this->getRawOriginal('price_breakdown');
        $lines = is_string($raw) ? json_decode($raw, true) : null;

        if (empty($lines)) {
            $start = $this->getRawOriginal('start_date');
            $end = $this->getRawOriginal('end_date');
            $lines = NightlyPricing::legacyLines(
                $start ? Carbon::parse($start)->toDateString() : null,
                $end ? Carbon::parse($end)->toDateString() : null,
                $this->getRawOriginal('price_for_period')
            );
        }

        return NightlyPricing::expand($lines);
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

    /**
     * Расчёт цены для путей, где её не посчитал вызывающий код (например, админка Voyager):
     * проживание по тарифам на период, налог — от проживания; доплаты и услуги
     * берутся из уже заполненных полей брони.
     */
    protected static function autoPrice(Booking $booking, Room $room, array $lockedNights): void
    {
        $calculator = app(BookingPriceCalculator::class);
        [$checkIn, $checkOut] = $calculator->plannedMoments($room, $booking->start_date, $booking->end_date);
        $breakdown = $calculator->calculate($room, $checkIn, $checkOut, null, $lockedNights);

        $booking->price_for_period = $breakdown['accommodation'];
        $booking->price_breakdown = $breakdown['price_breakdown'];
        $booking->tax = $breakdown['tax'];
        $booking->total_price = $breakdown['accommodation'] + $breakdown['tax']
            + ($booking->early_check_in_amount ?? 0)
            + ($booking->late_check_out_amount ?? 0)
            + ($booking->services_amount ?? 0);
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
            // Цену уже посчитал вызывающий код (BookingPriceCalculator::applyToBooking) — не трогаем.
            if ($booking->price_for_period !== null) {
                return;
            }
            self::autoPrice($booking, $room, []);
        });
        static::updating(function ($booking) {
            if ($booking->skipAutoPricing) {
                return;
            }
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
                // Сменили номер — цены прежнего номера не переносим; иначе ночи,
                // которые остались в брони, сохраняют уже зафиксированную цену.
                self::autoPrice($booking, $room, $booking->isDirty('room_id') ? [] : $booking->lockedNights());
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
        static::saved(function ($booking) {
            $booking->skipAutoPricing = false;
        });
    }
}
