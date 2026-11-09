<?php

namespace Tests\Unit;

use App\Enums\PaymentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_resolve(float $total, float $paid, PaymentStatus $expected): void
    {
        $this->assertSame($expected, PaymentStatus::resolve($total, $paid));
    }

    public static function cases(): array
    {
        return [
            'платежей нет'                     => [30000, 0,        PaymentStatus::Unpaid],
            'внесена часть'                    => [30000, 5000,     PaymentStatus::Prepaid],
            'на 1 тиын меньше полной суммы'    => [30000, 29999.99, PaymentStatus::Prepaid],
            'оплачено ровно полностью'         => [30000, 30000,    PaymentStatus::Paid],
            'переплата считается оплаченной'   => [30000, 35000,    PaymentStatus::Paid],
            'float-погрешность не мешает'      => [0.3,   0.1 + 0.2, PaymentStatus::Paid],
            'нулевая сумма без платежей'       => [0,     0,        PaymentStatus::Unpaid],
        ];
    }

    public function test_options_for_enums_endpoint(): void
    {
        $this->assertSame([
            ['value' => 'unpaid',  'label' => 'Не оплачено'],
            ['value' => 'prepaid', 'label' => 'Предоплата'],
            ['value' => 'paid',    'label' => 'Оплачено'],
        ], PaymentStatus::options());
    }
}
