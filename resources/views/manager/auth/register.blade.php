{{-- resources/views/manager/auth/register.blade.php --}}
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация менеджера отеля</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .logo {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            color: #111;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 28px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus { border-color: #6366f1; }
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 20px 0;
        }
        .section-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 14px;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
        }
        .btn:hover { background: #4f46e5; }
        .error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #dc2626;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #6b7280;
        }
        .login-link a { color: #6366f1; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">🏨 Nomad Hotels</div>
        <div class="subtitle">Регистрация менеджера отеля</div>

        @if ($errors->any())
            <div class="error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('manager.register') }}">
            @csrf

            <div class="section-label">Данные аккаунта</div>

            <div class="form-group">
                <label>Ваше имя</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="Иван Иванов" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="manager@hotel.com" required>
            </div>

            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" placeholder="Минимум 8 символов" required>
            </div>

            <div class="form-group">
                <label>Повторите пароль</label>
                <input type="password" name="password_confirmation" required>
            </div>

            <hr class="divider">
            <div class="section-label">Данные отеля</div>

            <div class="form-group">
                <label>Название отеля</label>
                <input type="text" name="hotel_title" value="{{ old('hotel_title') }}" placeholder="Grand Hotel Almaty" required>
            </div>

            <div class="form-group">
                <label>Адрес отеля</label>
                <input type="text" name="hotel_address" value="{{ old('hotel_address') }}" placeholder="г. Алматы, ул. Абая 1">
            </div>

            <button type="submit" class="btn">Зарегистрироваться</button>
        </form>

        <div class="login-link">
            Уже есть аккаунт? <a href="{{ route('admin.login') }}">Войти</a>
        </div>
    </div>
</body>
</html>