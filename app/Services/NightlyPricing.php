<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Посуточный расчёт стоимости проживания с учётом тарифов на период.
 *
 * Класс намеренно не зависит от Eloquent/Carbon: на вход — обычные массивы,
 * на выход — массив строк расшифровки. Так расчёт легко проверять в тестах
 * и одинаково использовать и для превью, и для сохранения брони.
 *
 * Термины:
 *  - «ночь» обозначается датой вечера заезда (ночь 1 декабря = с 1 на 2 декабря);
 *  - дата выезда не оплачивается как ночь;
 *  - границы тарифа date_from / date_to включительно, по датам ночей.
 *
 * Формат строки расшифровки (одна строка = подряд идущие ночи по одной цене):
 *  [
 *    'date_from'       => '2026-12-01', // первая ночь
 *    'date_to'         => '2026-12-14', // последняя ночь (включительно)
 *    'nights'          => 14,
 *    'price_per_night' => 25000,
 *    'amount'          => 350000,
 *    'rate_id'         => 7,            // null — базовая цена номера
 *    'rate_title'      => 'Новогодний', // null — базовая цена номера
 *  ]
 */
final class NightlyPricing
{
    /**
     * @param int|float $basePrice  базовая цена номера за ночь (rooms.price)
     * @param string $startDate     Y-m-d, дата заезда (первая ночь)
     * @param string $endDate       Y-m-d, дата выезда (не оплачивается)
     * @param array<int,array{id:int,title:?string,date_from:string,date_to:string,price:int|float}> $rates
     *        активные тарифы номера, пересекающие период (даты Y-m-d)
     * @param array<string,array{price:int|float,rate_id:?int,rate_title:?string}> $lockedNights
     *        ночи с зафиксированной ценой (дата => цена): они не пересчитываются
     *        по текущим тарифам — нужно, чтобы правка брони не меняла цену
     *        ночей, которые уже были оплачены по старым условиям
     * @return array{
     *     nights:int,
     *     total:int|float,
     *     first_night_price:int|float,
     *     last_night_price:int|float,
     *     lines:array<int,array<string,mixed>>
     * }
     */
    public static function resolve(
        int|float $basePrice,
        string $startDate,
        string $endDate,
        array $rates = [],
        array $lockedNights = []
    ): array {
        // Минимум одна ночь — как и в прежней логике калькулятора.
        if ($endDate <= $startDate) {
            $endDate = self::addDays($startDate, 1);
        }

        // Точки, в которых цена может измениться. Между двумя соседними точками
        // цена ночи одинакова, поэтому считаем по отрезкам, а не по каждой ночи:
        // время расчёта не зависит от длины периода.
        $points = [$startDate => true, $endDate => true];

        foreach ($rates as $rate) {
            $from = max($rate['date_from'], $startDate);
            $toExclusive = min(self::addDays($rate['date_to'], 1), $endDate);
            if ($from < $toExclusive) {
                $points[$from] = true;
                $points[$toExclusive] = true;
            }
        }

        foreach (array_keys($lockedNights) as $date) {
            if ($date >= $startDate && $date < $endDate) {
                $points[$date] = true;
                $points[self::addDays($date, 1)] = true;
            }
        }

        ksort($points);
        $dates = array_keys($points);

        $lines = [];
        for ($i = 0, $n = count($dates) - 1; $i < $n; $i++) {
            $from = $dates[$i];
            $nights = self::daysBetween($from, $dates[$i + 1]);
            $night = self::priceForNight($from, $basePrice, $rates, $lockedNights);

            $last = count($lines) - 1;
            if (
                $last >= 0
                && $lines[$last]['rate_id'] === $night['rate_id']
                && $lines[$last]['price_per_night'] == $night['price']
            ) {
                // Соседний отрезок с той же ценой — склеиваем в одну строку.
                $lines[$last]['nights'] += $nights;
                $lines[$last]['date_to'] = self::addDays($dates[$i + 1], -1);
                $lines[$last]['amount'] = $lines[$last]['nights'] * $night['price'];
                continue;
            }

            $lines[] = [
                'date_from' => $from,
                'date_to' => self::addDays($dates[$i + 1], -1),
                'nights' => $nights,
                'price_per_night' => $night['price'],
                'amount' => $nights * $night['price'],
                'rate_id' => $night['rate_id'],
                'rate_title' => $night['rate_title'],
            ];
        }

        $total = 0;
        $totalNights = 0;
        foreach ($lines as $line) {
            $total += $line['amount'];
            $totalNights += $line['nights'];
        }

        return [
            'nights' => $totalNights,
            'total' => $total,
            'first_night_price' => $lines[0]['price_per_night'],
            'last_night_price' => $lines[count($lines) - 1]['price_per_night'],
            'lines' => $lines,
        ];
    }

    /**
     * Разворачивает сохранённую расшифровку в карту «дата ночи => цена».
     * Результат подходит как $lockedNights для resolve().
     *
     * @param array<int,array<string,mixed>> $lines
     * @return array<string,array{price:int|float,rate_id:?int,rate_title:?string}>
     */
    public static function expand(array $lines): array
    {
        $map = [];
        foreach ($lines as $line) {
            if (!isset($line['date_from'], $line['date_to'], $line['price_per_night'])) {
                continue;
            }
            $count = self::daysBetween($line['date_from'], self::addDays($line['date_to'], 1));
            $date = $line['date_from'];
            for ($i = 0; $i < $count; $i++) {
                $map[$date] = [
                    'price' => $line['price_per_night'],
                    'rate_id' => $line['rate_id'] ?? null,
                    'rate_title' => $line['rate_title'] ?? null,
                ];
                $date = self::addDays($date, 1);
            }
        }

        return $map;
    }

    /**
     * Расшифровка для брони, созданной до появления тарифов по датам:
     * в ней нет сохранённой разбивки, но известна итоговая цена проживания.
     * Считаем, что все ночи стоили одинаково.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function legacyLines(?string $startDate, ?string $endDate, int|float|string|null $priceForPeriod): array
    {
        if (!$startDate || !$endDate || $priceForPeriod === null) {
            return [];
        }

        $startDate = substr($startDate, 0, 10);
        $endDate = substr($endDate, 0, 10);
        if ($endDate <= $startDate) {
            $endDate = self::addDays($startDate, 1);
        }

        $nights = self::daysBetween($startDate, $endDate);
        $amount = $priceForPeriod + 0;

        return [[
            'date_from' => $startDate,
            'date_to' => self::addDays($endDate, -1),
            'nights' => $nights,
            'price_per_night' => $amount / $nights,
            'amount' => $amount,
            'rate_id' => null,
            'rate_title' => null,
        ]];
    }

    /**
     * Цена одной ночи: зафиксированная → тариф на период → базовая цена номера.
     *
     * @return array{price:int|float,rate_id:?int,rate_title:?string}
     */
    private static function priceForNight(string $date, int|float $basePrice, array $rates, array $lockedNights): array
    {
        if (isset($lockedNights[$date])) {
            return [
                'price' => $lockedNights[$date]['price'],
                'rate_id' => $lockedNights[$date]['rate_id'] ?? null,
                'rate_title' => $lockedNights[$date]['rate_title'] ?? null,
            ];
        }

        $best = null;
        $bestSpan = null;
        foreach ($rates as $rate) {
            if ($rate['date_from'] > $date || $rate['date_to'] < $date) {
                continue;
            }
            // Если вдруг подходит несколько тарифов (в API пересечения запрещены,
            // но данные могли попасть в базу иначе): побеждает более узкий период,
            // при равенстве — созданный позже. Результат не зависит от порядка выборки.
            $span = self::daysBetween($rate['date_from'], $rate['date_to']);
            if (
                $best === null
                || $span < $bestSpan
                || ($span === $bestSpan && $rate['id'] > $best['id'])
            ) {
                $best = $rate;
                $bestSpan = $span;
            }
        }

        if ($best !== null) {
            return [
                'price' => $best['price'],
                'rate_id' => $best['id'],
                'rate_title' => $best['title'] ?? null,
            ];
        }

        return ['price' => $basePrice, 'rate_id' => null, 'rate_title' => null];
    }

    public static function addDays(string $date, int $days): string
    {
        return self::parse($date)->modify(($days >= 0 ? '+' : '') . $days . ' day')->format('Y-m-d');
    }

    public static function daysBetween(string $from, string $to): int
    {
        return intdiv(self::parse($to)->getTimestamp() - self::parse($from)->getTimestamp(), 86400);
    }

    private static function parse(string $date): DateTimeImmutable
    {
        // UTC, чтобы переход на летнее время не давал «23-часовых» суток.
        return new DateTimeImmutable(substr($date, 0, 10) . ' 00:00:00', new DateTimeZone('UTC'));
    }
}
