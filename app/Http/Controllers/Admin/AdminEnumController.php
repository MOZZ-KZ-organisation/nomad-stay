<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminEnumController extends Controller
{
    /**
     * GET /admin-api/enums
     * Все статичные справочники для фронта — статусы, источники, цвета.
     */
    public function index()
    {
        return response()->json([
            'data' => [

                // Статусы бронирования (Booking::status) + цвета для календаря
                'booking_statuses' => [
                    ['value' => 'booked',      'label' => 'Забронировано', 'color' => '#ffd97f'],
                    ['value' => 'checked_in',  'label' => 'Заселён',       'color' => '#a9d445'],
                    ['value' => 'checked_out', 'label' => 'Выселен',       'color' => '#b5b7b9'],
                    ['value' => 'cancelled',   'label' => 'Отменено',      'color' => '#EB5757'],
                ],

                // Источники бронирования (Booking::source)
                'booking_sources' => [
                    ['value' => 'site',        'label' => 'Сайт или приложение'],
                    ['value' => 'booking.com', 'label' => 'Booking.com'],
                    ['value' => 'manual',      'label' => 'Вручную'],
                ],

                // Тип бронирования (Booking::type)
                'booking_types' => [
                    ['value' => 'booking',  'label' => 'Бронирование'],
                    ['value' => 'hourly',   'label' => 'Почасовое'],
                ],

                // Статусы периодов номера (RoomPeriod::status) + цвета для календаря
                'room_period_statuses' => [
                    ['value' => 'free',        'label' => 'Свободен', 'color' => '#FFFFFF'],
                    ['value' => 'cleaning',    'label' => 'Уборка',   'color' => '#F4A261'],
                    ['value' => 'maintenance', 'label' => 'Ремонт',   'color' => '#9B9B9B'],
                ],

            ],
        ]);
    }
}