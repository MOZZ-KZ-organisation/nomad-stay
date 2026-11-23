<?php

namespace Tests\Unit;

use App\Services\NightlyPricing;
use PHPUnit\Framework\TestCase;

class NightlyPricingTest extends TestCase
{
    private function rate(int $id, string $title, string $from, string $to, int $price): array
    {
        return ['id' => $id, 'title' => $title, 'date_from' => $from, 'date_to' => $to, 'price' => $price];
    }

    public function test_without_rates_uses_base_price(): void
    {
        $r = NightlyPricing::resolve(10000, '2026-12-01', '2026-12-04');

        $this->assertSame(3, $r['nights']);
        $this->assertSame(30000, $r['total']);
        $this->assertCount(1, $r['lines']);
        $this->assertNull($r['lines'][0]['rate_id']);
        $this->assertSame('2026-12-01', $r['lines'][0]['date_from']);
        $this->assertSame('2026-12-03', $r['lines'][0]['date_to']);
    }

    public function test_rate_covering_whole_stay(): void
    {
        $rates = [$this->rate(1, 'Зима', '2026-12-01', '2027-01-15', 25000)];
        $r = NightlyPricing::resolve(10000, '2026-12-10', '2026-12-12', $rates);

        $this->assertSame(2, $r['nights']);
        $this->assertSame(50000, $r['total']);
        $this->assertSame(1, $r['lines'][0]['rate_id']);
        $this->assertSame('Зима', $r['lines'][0]['rate_title']);
    }

    public function test_stay_crossing_rate_start_is_split_into_lines(): void
    {
        // Ночи 29 и 30 ноября — по базе, 1 и 2 декабря — по тарифу.
        $rates = [$this->rate(1, 'Зима', '2026-12-01', '2027-01-15', 25000)];
        $r = NightlyPricing::resolve(10000, '2026-11-29', '2026-12-03', $rates);

        $this->assertSame(4, $r['nights']);
        $this->assertSame(2 * 10000 + 2 * 25000, $r['total']);
        $this->assertCount(2, $r['lines']);
        $this->assertSame(['2026-11-29', '2026-11-30', 2, 10000, null], [
            $r['lines'][0]['date_from'], $r['lines'][0]['date_to'], $r['lines'][0]['nights'],
            $r['lines'][0]['price_per_night'], $r['lines'][0]['rate_id'],
        ]);
        $this->assertSame(['2026-12-01', '2026-12-02', 2, 25000, 1], [
            $r['lines'][1]['date_from'], $r['lines'][1]['date_to'], $r['lines'][1]['nights'],
            $r['lines'][1]['price_per_night'], $r['lines'][1]['rate_id'],
        ]);
        $this->assertSame(10000, $r['first_night_price']);
        $this->assertSame(25000, $r['last_night_price']);
    }

    public function test_date_to_is_inclusive_for_nights(): void
    {
        // Тариф «по 15 января»: ночь с 15 на 16 января ещё по тарифу, с 16 на 17 — уже по базе.
        $rates = [$this->rate(1, 'Зима', '2026-12-01', '2027-01-15', 25000)];
        $r = NightlyPricing::resolve(10000, '2027-01-15', '2027-01-17', $rates);

        $this->assertSame(25000 + 10000, $r['total']);
        $this->assertCount(2, $r['lines']);
        $this->assertSame(1, $r['lines'][0]['rate_id']);
        $this->assertNull($r['lines'][1]['rate_id']);
    }

    public function test_checkout_day_is_not_charged(): void
    {
        // Выезд 1 декабря: ночь на 1 декабря тарифом не считается, тариф с 1 декабря не применяется.
        $rates = [$this->rate(1, 'Зима', '2026-12-01', '2027-01-15', 25000)];
        $r = NightlyPricing::resolve(10000, '2026-11-30', '2026-12-01', $rates);

        $this->assertSame(1, $r['nights']);
        $this->assertSame(10000, $r['total']);
    }

    public function test_two_rates_in_one_stay(): void
    {
        $rates = [
            $this->rate(1, 'Высокий', '2026-12-01', '2026-12-31', 30000),
            $this->rate(2, 'Каникулы', '2027-01-01', '2027-01-10', 40000),
        ];
        $r = NightlyPricing::resolve(10000, '2026-12-30', '2027-01-03', $rates);

        $this->assertSame(4, $r['nights']);
        $this->assertSame(2 * 30000 + 2 * 40000, $r['total']);
        $this->assertCount(2, $r['lines']);
    }

    public function test_adjacent_rates_with_same_price_are_not_merged(): void
    {
        // Разные тарифы с одинаковой ценой остаются разными строками: клиенту видно, какой тариф сработал.
        $rates = [
            $this->rate(1, 'А', '2026-12-01', '2026-12-05', 30000),
            $this->rate(2, 'Б', '2026-12-06', '2026-12-10', 30000),
        ];
        $r = NightlyPricing::resolve(10000, '2026-12-04', '2026-12-08', $rates);

        $this->assertSame(4 * 30000, $r['total']);
        $this->assertCount(2, $r['lines']);
    }

    public function test_narrower_rate_wins_when_rates_overlap(): void
    {
        $rates = [
            $this->rate(1, 'Сезон', '2026-12-01', '2027-01-31', 20000),
            $this->rate(2, 'Новый год', '2026-12-31', '2027-01-02', 50000),
        ];
        $forward = NightlyPricing::resolve(10000, '2026-12-30', '2027-01-04', $rates);
        $backward = NightlyPricing::resolve(10000, '2026-12-30', '2027-01-04', array_reverse($rates));

        // 30.12 — сезон; 31.12, 1.01, 2.01 — новый год; 3.01 — сезон.
        $this->assertSame(20000 + 3 * 50000 + 20000, $forward['total']);
        $this->assertSame($forward, $backward);
    }

    public function test_rate_outside_the_stay_is_ignored(): void
    {
        $rates = [$this->rate(1, 'Лето', '2026-06-01', '2026-08-31', 99999)];
        $r = NightlyPricing::resolve(10000, '2026-12-01', '2026-12-03', $rates);

        $this->assertSame(20000, $r['total']);
        $this->assertNull($r['lines'][0]['rate_id']);
    }

    public function test_locked_nights_keep_their_price_when_stay_is_extended(): void
    {
        // Бронь была на 1–3 декабря по 10000. Потом появился тариф 25000 на весь декабрь,
        // а бронь продлили до 5 декабря: старые ночи остаются по 10000, новые — по тарифу.
        $locked = NightlyPricing::expand(NightlyPricing::resolve(10000, '2026-12-01', '2026-12-03')['lines']);
        $rates = [$this->rate(1, 'Декабрь', '2026-12-01', '2026-12-31', 25000)];

        $r = NightlyPricing::resolve(10000, '2026-12-01', '2026-12-05', $rates, $locked);

        $this->assertSame(2 * 10000 + 2 * 25000, $r['total']);
        $this->assertCount(2, $r['lines']);
    }

    public function test_locked_nights_outside_new_dates_are_ignored(): void
    {
        $locked = NightlyPricing::expand(NightlyPricing::resolve(10000, '2026-12-01', '2026-12-04')['lines']);

        $r = NightlyPricing::resolve(10000, '2026-12-02', '2026-12-03', [], $locked);

        $this->assertSame(1, $r['nights']);
        $this->assertSame(10000, $r['total']);
    }

    public function test_expand_roundtrip_keeps_every_night(): void
    {
        $rates = [$this->rate(7, 'Зима', '2026-12-03', '2026-12-04', 25000)];
        $lines = NightlyPricing::resolve(10000, '2026-12-01', '2026-12-06', $rates)['lines'];
        $map = NightlyPricing::expand($lines);

        $this->assertSame(['2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05'], array_keys($map));
        $this->assertSame(25000, $map['2026-12-03']['price']);
        $this->assertSame(7, $map['2026-12-04']['rate_id']);
        $this->assertSame(10000, $map['2026-12-05']['price']);
    }

    public function test_legacy_lines_from_total_price(): void
    {
        $lines = NightlyPricing::legacyLines('2026-12-01', '2026-12-04', 30000);

        $this->assertCount(1, $lines);
        $this->assertSame(3, $lines[0]['nights']);
        $this->assertEquals(10000, $lines[0]['price_per_night']);
        $this->assertSame('2026-12-03', $lines[0]['date_to']);
        $this->assertSame([], NightlyPricing::legacyLines(null, null, null));
    }

    public function test_zero_night_stay_is_charged_as_one_night(): void
    {
        $r = NightlyPricing::resolve(10000, '2026-12-01', '2026-12-01');

        $this->assertSame(1, $r['nights']);
        $this->assertSame(10000, $r['total']);
    }

    public function test_very_long_stay_is_computed_without_per_night_iteration(): void
    {
        $started = microtime(true);
        $r = NightlyPricing::resolve(10000, '2026-01-01', '9999-12-31', [
            $this->rate(1, 'Всегда', '2026-01-01', '9999-12-31', 20000),
        ]);

        $this->assertLessThan(1.0, microtime(true) - $started);
        $this->assertCount(1, $r['lines']);
    }
}
