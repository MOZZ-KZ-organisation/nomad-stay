<?php

namespace App\Enums;

/**
 * Статус оплаты брони. Не хранится в БД — всегда вычисляется по платежам
 * (сумма completed-платежей против итоговой суммы брони).
 */
enum PaymentStatus: string
{
    /** Платежей нет: paid_amount = 0. */
    case Unpaid = 'unpaid';

    /** Внесена часть суммы: 0 < paid_amount < total_price. */
    case Prepaid = 'prepaid';

    /** Оплачено полностью: paid_amount >= total_price. */
    case Paid = 'paid';

    public static function resolve(float $totalPrice, float $paidAmount): self
    {
        // Сравниваем в копейках/тиынах, чтобы не споткнуться о float-погрешность.
        $total = (int) round($totalPrice * 100);
        $paid  = (int) round($paidAmount * 100);

        if ($paid <= 0) {
            return self::Unpaid;
        }

        return $paid >= $total ? self::Paid : self::Prepaid;
    }

    public function label(): string
    {
        return match ($this) {
            self::Unpaid  => 'Не оплачено',
            self::Prepaid => 'Предоплата',
            self::Paid    => 'Оплачено',
        };
    }

    /** Для справочника GET /admin-api/enums. */
    public static function options(): array
    {
        return array_map(
            fn (self $s) => ['value' => $s->value, 'label' => $s->label()],
            self::cases()
        );
    }
}
