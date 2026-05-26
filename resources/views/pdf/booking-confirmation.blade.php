<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение бронирования {{ $booking->booking_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .booking-number {
            font-size: 24px;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 5px;
            display: inline-block;
            margin-top: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .info-item {
            padding: 10px 0;
        }
        
        .info-label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }
        
        .hotel-info {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .hotel-name {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stars {
            color: #ffa500;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .address {
            color: #666;
            font-size: 14px;
        }
        
        .price-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .price-table tr {
            border-bottom: 1px solid #eee;
        }
        
        .price-table td {
            padding: 12px 0;
        }
        
        .price-table .label {
            color: #666;
        }
        
        .price-table .value {
            text-align: right;
            font-weight: 600;
        }
        
        .price-table .total {
            font-size: 20px;
            color: #667eea;
            font-weight: bold;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-booked {
            background: #ffd97f;
            color: #856404;
        }
        
        .status-confirmed {
            background: #a9d445;
            color: #155724;
        }
        
        .status-paid {
            background: #28a745;
            color: white;
        }
        
        .footer {
            background: #f9f9f9;
            padding: 20px 30px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        
        .print-button {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            cursor: pointer;
            border: none;
            font-size: 16px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>ПОДТВЕРЖДЕНИЕ БРОНИРОВАНИЯ</h1>
            <div class="booking-number">{{ $booking->booking_number }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Hotel Information -->
            <div class="section">
                <div class="hotel-info">
                    <div class="hotel-name">{{ $hotel->title }}</div>
                    <div class="stars">
                        @for($i = 0; $i < $hotel->stars; $i++)
                            ★
                        @endfor
                    </div>
                    <div class="address">
                        📍 {{ $hotel->address }}, {{ $city->name }}, {{ $country->name }}
                    </div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="section">
                <div class="section-title">Детали бронирования</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Заезд</div>
                        <div class="info-value">{{ $booking->start_date->format('d.m.Y') }}</div>
                        @if($booking->arrival_time)
                            <div style="font-size: 14px; color: #666; margin-top: 5px;">
                                Время: {{ $booking->arrival_time->format('H:i') }}
                            </div>
                        @endif
                    </div>
                    <div class="info-item">
                        <div class="info-label">Выезд</div>
                        <div class="info-value">{{ $booking->end_date->format('d.m.Y') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Количество ночей</div>
                        <div class="info-value">{{ $nights }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Количество гостей</div>
                        <div class="info-value">{{ $booking->guests }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Тип номера</div>
                        <div class="info-value">{{ $room->title }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Статус</div>
                        <div class="info-value">
                            <span class="status-badge status-{{ $booking->status }}">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guest Information -->
            <div class="section">
                <div class="section-title">Информация о госте</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Имя</div>
                        <div class="info-value">{{ $booking->full_name }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">{{ $booking->email }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value">{{ $booking->phone }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Страна</div>
                        <div class="info-value">{{ $booking->country }}</div>
                    </div>
                </div>

                @if($booking->special_requests)
                    <div class="info-item" style="margin-top: 15px;">
                        <div class="info-label">Особые пожелания</div>
                        <div class="info-value" style="font-weight: normal; color: #666;">
                            {{ $booking->special_requests }}
                        </div>
                    </div>
                @endif
            </div>

            <!-- Price Breakdown -->
            <div class="section">
                <div class="section-title">Детали оплаты</div>
                <table class="price-table">
                    <tr>
                        <td class="label">{{ $nights }} ночей × {{ number_format($room->price, 0, ',', ' ') }} ₸</td>
                        <td class="value">{{ number_format($booking->price_for_period, 0, ',', ' ') }} ₸</td>
                    </tr>
                    @if($booking->tax > 0)
                    <tr>
                        <td class="label">Налоги и сборы</td>
                        <td class="value">{{ number_format($booking->tax, 0, ',', ' ') }} ₸</td>
                    </tr>
                    @endif
                    <tr style="border-top: 2px solid #667eea;">
                        <td class="label total">ИТОГО</td>
                        <td class="value total">{{ number_format($booking->total_price, 0, ',', ' ') }} ₸</td>
                    </tr>
                    <tr>
                        <td class="label">Статус оплаты</td>
                        <td class="value">
                            @if($booking->is_paid)
                                <span class="status-badge status-paid">Оплачено</span>
                            @else
                                <span class="status-badge status-booked">Ожидает оплаты</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Print Button -->
            <div style="text-align: center;">
                <button onclick="window.print()" class="print-button">
                    Распечатать / Сохранить как PDF
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Дата бронирования: {{ $booking->created_at->format('d.m.Y H:i') }}</p>
            <p style="margin-top: 10px;">Спасибо за выбор Nomad Stay!</p>
            <p style="margin-top: 5px; font-size: 12px;">
                При заселении необходимо предъявить данное подтверждение и документ, удостоверяющий личность
            </p>
        </div>
    </div>
</body>
</html>