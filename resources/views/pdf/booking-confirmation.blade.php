<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Подтверждение бронирования {{ $booking->booking_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: #1a1a1a;
            background: #f8f9fa;
            padding: 40px 20px;
            -webkit-font-smoothing: antialiased;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            color: #ffffff;
            padding: 40px 40px 35px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .logo {
            font-size: 20px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        
        .header h1 {
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            opacity: 0.9;
        }
        
        .booking-number {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-top: 5px;
        }
        
        /* Content */
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 35px;
            padding-bottom: 35px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: 600;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }
        
        /* Hotel Info */
        .hotel-info {
            background: #f8f9fa;
            padding: 24px;
            border-radius: 8px;
            border-left: 3px solid #4A90E2;
        }
        
        .hotel-name {
            font-size: 20px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            letter-spacing: -0.3px;
        }
        
        .stars {
            display: inline-flex;
            gap: 2px;
            margin-bottom: 12px;
        }
        
        .star {
            width: 14px;
            height: 14px;
            background: #FFA726;
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%);
        }
        
        .address {
            color: #5f6368;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 11px;
            color: #80868b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            font-weight: 500;
        }
        
        .info-value {
            font-size: 15px;
            font-weight: 500;
            color: #1a1a1a;
        }
        
        .info-value-large {
            font-size: 18px;
            font-weight: 600;
        }
        
        .info-sub {
            font-size: 13px;
            color: #5f6368;
            margin-top: 4px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .status-booked {
            background: #FFF3E0;
            color: #E65100;
        }
        
        .status-confirmed {
            background: #E8F5E9;
            color: #2E7D32;
        }
        
        .status-checked_in {
            background: #E3F2FD;
            color: #1565C0;
        }
        
        .status-paid {
            background: #E8F5E9;
            color: #2E7D32;
        }
        
        /* Price Table */
        .price-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .price-table tr {
            border-bottom: 1px solid #f1f3f4;
        }
        
        .price-table tr:last-child {
            border-bottom: none;
        }
        
        .price-table td {
            padding: 14px 0;
            font-size: 14px;
        }
        
        .price-table .label {
            color: #5f6368;
            font-weight: 400;
        }
        
        .price-table .value {
            text-align: right;
            font-weight: 500;
            color: #1a1a1a;
        }
        
        .price-table .total-row {
            border-top: 2px solid #e8eaed;
            margin-top: 8px;
        }
        
        .price-table .total-row td {
            padding-top: 18px;
            font-size: 16px;
        }
        
        .price-table .total {
            font-weight: 700;
            color: #4A90E2;
        }
        
        /* Footer */
        .footer {
            background: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e8eaed;
        }
        
        .footer-text {
            color: #5f6368;
            font-size: 13px;
            line-height: 1.6;
        }
        
        .footer-date {
            color: #80868b;
            font-size: 12px;
            margin-top: 12px;
        }
        
        /* Print Button */
        .print-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e8eaed;
        }
        
        .print-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #4A90E2;
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
            border: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(74, 144, 226, 0.2);
        }
        
        .print-button:hover {
            background: #357ABD;
            box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3);
            transform: translateY(-1px);
        }
        
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .print-section {
                display: none;
            }
        }
        
        @media (max-width: 640px) {
            body {
                padding: 20px 10px;
            }
            
            .content {
                padding: 30px 20px;
            }
            
            .header {
                padding: 30px 20px 25px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .booking-number {
                font-size: 22px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">NOMAD STAY</div>
            <h1>Подтверждение бронирования</h1>
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
                            <div class="star"></div>
                        @endfor
                    </div>
                    <div class="address">{{ $hotel->address }}, {{ $city->name }}, {{ $country->name }}</div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="section">
                <div class="section-title">Детали бронирования</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Заезд</div>
                        <div class="info-value info-value-large">{{ $booking->start_date->format('d.m.Y') }}</div>
                        @if($booking->arrival_time)
                            <div class="info-sub">{{ $booking->arrival_time->format('H:i') }}</div>
                        @endif
                    </div>
                    <div class="info-item">
                        <div class="info-label">Выезд</div>
                        <div class="info-value info-value-large">{{ $booking->end_date->format('d.m.Y') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Продолжительность</div>
                        <div class="info-value">{{ $nights }} {{ $nights == 1 ? 'ночь' : ($nights < 5 ? 'ночи' : 'ночей') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Гостей</div>
                        <div class="info-value">{{ $booking->guests }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Тип номера</div>
                        <div class="info-value">{{ $room->title }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Статус бронирования</div>
                        <div>
                            <span class="status-badge status-{{ $booking->status }}">
                                @if($booking->status == 'booked') Забронировано
                                @elseif($booking->status == 'confirmed') Подтверждено
                                @elseif($booking->status == 'checked_in') Заселен
                                @elseif($booking->status == 'checked_out') Выселен
                                @else {{ ucfirst($booking->status) }}
                                @endif
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
                        <div class="info-label">Имя и фамилия</div>
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
                    <div class="info-item" style="margin-top: 24px;">
                        <div class="info-label">Особые пожелания</div>
                        <div class="info-value" style="font-weight: 400; color: #5f6368; margin-top: 8px;">
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
                        <td class="label">{{ $nights }} {{ $nights == 1 ? 'ночь' : ($nights < 5 ? 'ночи' : 'ночей') }} × {{ number_format($room->price, 0, ',', ' ') }} ₸</td>
                        <td class="value">{{ number_format($booking->price_for_period, 0, ',', ' ') }} ₸</td>
                    </tr>
                    @if($booking->tax > 0)
                    <tr>
                        <td class="label">Налоги и сборы</td>
                        <td class="value">{{ number_format($booking->tax, 0, ',', ' ') }} ₸</td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td class="label total">Итого к оплате</td>
                        <td class="value total">{{ number_format($booking->total_price, 0, ',', ' ') }} ₸</td>
                    </tr>
                </table>
                
                <div style="margin-top: 20px;">
                    <div class="info-label">Статус оплаты</div>
                    <div style="margin-top: 8px;">
                        @if($booking->is_paid)
                            <span class="status-badge status-paid">Оплачено</span>
                        @else
                            <span class="status-badge status-booked">Ожидает оплаты</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Print Button -->
            <div class="print-section">
                <button onclick="window.print()" class="print-button">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 5V2H4V5H2C1.45 5 1 5.45 1 6V11H4V14H12V11H15V6C15 5.45 14.55 5 14 5H12ZM5 3H11V5H5V3ZM11 13H5V9H11V13ZM12 8C11.45 8 11 7.55 11 7C11 6.45 11.45 6 12 6C12.55 6 13 6.45 13 7C13 7.55 12.55 8 12 8Z" fill="currentColor"/>
                    </svg>
                    Распечатать подтверждение
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                При заселении необходимо предъявить данное подтверждение<br>
                и документ, удостоверяющий личность
            </p>
            <p class="footer-date">Дата бронирования: {{ $booking->created_at->format('d.m.Y в H:i') }}</p>
        </div>
    </div>
</body>
</html>