<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    /**
     * GET /admin-api/bookings/{id}/payments
     */
    public function index(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);

        $payments = $booking->payments()->with('user:id,name')->latest('paid_at')->get();

        return response()->json([
            'data' => $payments->map(fn ($p) => $this->format($p)),
            'total_price' => (float) $booking->total_price,
            'paid_amount' => $booking->paid_amount,
            'balance_due' => $booking->balance_due,
        ]);
    }

    /**
     * POST /admin-api/bookings/{id}/payments
     * Зафиксировать оплату (полную или частичную) или возврат (отрицательная сумма / статус refunded).
     */
    public function store(Request $request, $bookingId)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,card,transfer,online,other',
            'paid_at' => 'nullable|date',
            'status' => 'nullable|in:pending,completed,refunded,failed',
            'operation_number' => 'nullable|string|max:100',
            'comment' => 'nullable|string|max:255',
        ]);

        $payment = Payment::create([
            ...$data,
            'booking_id' => $booking->id,
            'paid_at' => $data['paid_at'] ?? now(),
            'status' => $data['status'] ?? 'completed',
            'user_id' => $request->user()->id,
        ]);

        // is_paid держим как удобный флаг "оплачено полностью" для старых мест кода/фронта,
        // источник истины теперь — сумма completed-платежей (см. Booking::getBalanceDueAttribute).
        $booking->refresh();
        $booking->is_paid = $booking->balance_due <= 0;
        $booking->saveQuietly();

        return response()->json([
            'message' => 'Оплата зафиксирована',
            'data' => $this->format($payment->load('user:id,name')),
            'balance_due' => $booking->balance_due,
        ], 201);
    }

    public function update(Request $request, $bookingId, $id)
    {
        $booking = Booking::findOrFail($bookingId);
        $this->authorizeHotel($request->user(), $booking->hotel_id);
        $payment = Payment::where('booking_id', $bookingId)->findOrFail($id);

        $data = $request->validate([
            'status' => 'sometimes|in:pending,completed,refunded,failed',
            'comment' => 'nullable|string|max:255',
            'operation_number' => 'nullable|string|max:100',
        ]);
        $payment->update($data);

        $booking->refresh();
        $booking->is_paid = $booking->balance_due <= 0;
        $booking->saveQuietly();

        return response()->json([
            'message' => 'Платёж обновлён',
            'data' => $this->format($payment->fresh('user:id,name')),
            'balance_due' => $booking->balance_due,
        ]);
    }

    protected function format(Payment $p): array
    {
        return [
            'id' => $p->id,
            'amount' => (float) $p->amount,
            'method' => $p->method,
            'status' => $p->status,
            'paid_at' => $p->paid_at?->format('Y-m-d H:i'),
            'operation_number' => $p->operation_number,
            'comment' => $p->comment,
            'user' => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name] : null,
        ];
    }

    protected function authorizeHotel($user, int $hotelId): void
    {
        if ($user->isHotelManager() && $user->managedHotel?->id !== $hotelId) {
            abort(403, 'Нет доступа к этому отелю');
        }
    }
}