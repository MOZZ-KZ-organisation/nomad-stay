# Тарифы на период по датам — API для фронтенда

Цена номера может зависеть от дат: «с 25 декабря по 10 января — 25 000 за ночь».
Цена учитывается и в админке, и при онлайн-бронировании гостями. Перед сохранением брони
доступно превью с расшифровкой суммы.

Примеры ниже: отель `id=1`, номера `10` «Стандарт» (базовая цена 10 000) и `11` «Делюкс» (18 000),
тариф `5` «Новогодние». Реальные id, адреса и названия услуг будут другими.

## Общее

- Заголовки: `Authorization: Bearer <token>`, `Accept: application/json`.
- Админка — `/admin-api/...`, сайт — `/api/...`.
- Менеджер работает со своим отелем автоматически. Админу в запросах к `period-rates`
  нужно передавать `hotel_id` (в query или в теле).
- Ошибки валидации — стандартный формат Laravel: `{"message": "...", "errors": {"поле": ["..."]}}`.

## Как считается цена

- Проживание считается **по ночам**. Ночь обозначается датой вечера заезда (ночь 25 декабря — с 25 на 26).
- День выезда как ночь не оплачивается.
- `date_from` и `date_to` тарифа **включительно**: тариф «по 15 января» покрывает ночь с 15 на 16 января.
- Для каждой ночи берётся тариф на период, а если его нет — базовая цена номера (`rooms.price`).
- У одного номера активные тарифы **не могут пересекаться** по датам — API вернёт `422` со списком конфликтов.
- Тариф влияет только на новые и пересчитываемые брони. Созданная бронь хранит посуточный
  снимок цены (`price_breakdown`), поэтому правка, удаление или восстановление тарифа её цену не меняют.
- При смене дат брони ночи, которые уже были в ней, сохраняют прежнюю цену, по текущим тарифам считаются только новые ночи.
- Доплата за ранний заезд / поздний выезд в процентах считается от цены первой / последней ночи.
- Суммы приходят числами; десятичные поля в `GET /api/bookings/{id}` (`tax`, `total_price`) — строками (`"81000.00"`).

### Строка расшифровки `price_breakdown`

```json
{ "date_from": "2026-12-25", "date_to": "2026-12-26", "nights": 2,
  "price_per_night": 25000, "amount": 50000, "rate_id": 5, "rate_title": "Новогодние" }
```

- `date_from` / `date_to` — первая и последняя ночь включительно.
- `rate_id: null` и `rate_title: null` — базовая цена номера.
- Подряд идущие ночи с одной ценой склеиваются в одну строку.

---

## 1. Тарифы на период (админка)

### Список — `GET /admin-api/period-rates?hotel_id=1`

Необязательные фильтры: `room_id`, `is_active`, `date_from` + `date_to` (пересечение с периодом),
`with_trashed=1` (вместе с удалёнными), `only_trashed=1` (только удалённые — корзина).

```json
{
  "data": [
    {
      "id": 5,
      "title": "Новогодние",
      "date_from": "2026-12-25",
      "date_to": "2027-01-10",
      "nights": 17,
      "price": 25000,
      "currency": "KZT",
      "is_active": true,
      "room_ids": [10, 11],
      "rooms": [
        { "id": 10, "title": "Стандарт" },
        { "id": 11, "title": "Делюкс" }
      ],
      "deleted_at": null
    }
  ]
}
```

### Один тариф — `GET /admin-api/period-rates/5?hotel_id=1`

Ответ: `{ "data": { ...тот же объект... } }`. Для удалённого тарифа в `deleted_at` будет дата.

### Создание — `POST /admin-api/period-rates`

```json
{
  "hotel_id": 1,
  "title": "Новогодние",
  "date_from": "2026-12-25",
  "date_to": "2027-01-10",
  "price": 25000,
  "room_ids": [10, 11],
  "is_active": true
}
```

Ответ `201`: `{ "message": "Тариф создан", "data": { ...тариф... } }`.

Ошибки `422`:

```json
// дата окончания раньше начала
{ "message": "The date to field must be a date after or equal to date from.",
  "errors": { "date_to": ["The date to field must be a date after or equal to date from."] } }

// номер из другого отеля
{ "message": "Номера не найдены в этом отеле: 99",
  "errors": { "room_ids": ["Номера не найдены в этом отеле: 99"] } }

// пересечение с другим активным тарифом на тех же номерах
{
  "message": "Тариф пересекается по датам с другим тарифом на тех же номерах: «Каникулы» (2027-01-05 — 2027-01-20; номера: Стандарт)",
  "errors": { "date_from": ["Период пересекается с другим активным тарифом"] },
  "conflicts": [
    { "id": 7, "title": "Каникулы", "date_from": "2027-01-05", "date_to": "2027-01-20",
      "rooms": [{ "id": 10, "title": "Стандарт" }] }
  ]
}
```

### Редактирование — `PATCH /admin-api/period-rates/5`

Все поля необязательные. `room_ids`, если передан, заменяет набор номеров целиком.

```json
{ "price": 28000, "room_ids": [10, 11, 12] }
```

Ответ `200`: `{ "message": "Тариф обновлён", "data": { ...тариф... } }`.
Ошибки те же, что при создании. Удалённый тариф редактировать нельзя — сначала восстановите.

### Удаление — `DELETE /admin-api/period-rates/5?hotel_id=1`

```json
{ "message": "Тариф удалён (его можно восстановить)" }
```

Удаление мягкое: тариф и привязка к номерам сохраняются.

### Восстановление — `POST /admin-api/period-rates/5/restore?hotel_id=1`

```json
{ "message": "Тариф восстановлен", "data": { "id": 5, "...": "..." } }
```

Ошибки `422`: `{ "message": "Тариф и так активен" }`, либо ответ с `conflicts` (как выше), если за время
удаления на те же номера и даты появился другой активный тариф.

---

## 2. Превью цены перед сохранением брони

### Админка — `GET /admin-api/bookings/price-preview`

Параметры: `room_id`, `start_date`, `end_date` (обязательные); `planned_check_in_time`,
`planned_check_out_time` (`HH:mm`); `booking_id` — для превью существующей брони.

Пример: `?room_id=10&start_date=2026-12-23&end_date=2026-12-27&planned_check_in_time=14:00&planned_check_out_time=12:00`

```json
{
  "available": true,
  "room_id": 10,
  "nights": 4,
  "price_per_night": 10000,
  "price_for_period": 70000.0,
  "price_breakdown": [
    { "date_from": "2026-12-23", "date_to": "2026-12-24", "nights": 2,
      "price_per_night": 10000, "amount": 20000, "rate_id": null, "rate_title": null },
    { "date_from": "2026-12-25", "date_to": "2026-12-26", "nights": 2,
      "price_per_night": 25000, "amount": 50000, "rate_id": 5, "rate_title": "Новогодние" }
  ],
  "early_check_in": 0.0,
  "late_check_out": 0.0,
  "services": 0.0,
  "tax_rate": 0.0,
  "tax": 0.0,
  "total_price": 70000.0,
  "currency": "KZT",
  "planned_check_in_at": "2026-12-23 14:00",
  "planned_check_out_at": "2026-12-27 12:00"
}
```

- `price_per_night` — **базовая** цена номера. Реальная цена каждой ночи — в `price_breakdown`.
- Если номер занят, `available: false`, расчёт всё равно возвращается.
- С `booking_id` ночи, уже посчитанные в брони, сохраняют свою цену, а сама бронь не считается занимающей номер.

### Сайт — `GET /api/bookings/price-details`

Параметры: `room_id`, `start_date`, `end_date` (обязательные); `arrival_time`, `departure_time`,
`services[0][service_id]`, `services[0][quantity]`, `booking_id`.

Пример: `?room_id=10&start_date=2026-12-23&end_date=2026-12-27&arrival_time=10:00&services[0][service_id]=3&services[0][quantity]=2`

Ранний заезд даёт доплату (правило «50% от ночи» → 5000 от первой ночи по 10 000), плюс две услуги по 3000:

```json
{
  "available": true,
  "room_id": 10,
  "nights": 4,
  "price_per_night": 10000,
  "price_for_period": 70000.0,
  "price_breakdown": [
    { "date_from": "2026-12-23", "date_to": "2026-12-24", "nights": 2, "price_per_night": 10000, "amount": 20000, "rate_id": null, "rate_title": null },
    { "date_from": "2026-12-25", "date_to": "2026-12-26", "nights": 2, "price_per_night": 25000, "amount": 50000, "rate_id": 5, "rate_title": "Новогодние" }
  ],
  "early_check_in": 5000.0,
  "late_check_out": 0.0,
  "services": 6000.0,
  "tax_rate": 0.0,
  "tax": 0.0,
  "total_price": 81000.0,
  "currency": "KZT",
  "cancellation_fee": 5000
}
```

Если номер занят, `available: false`, а `total_price: null`.

Сумма в превью совпадает с суммой, которая сохранится в брони.

---

## 3. Создание брони

### Сайт — `POST /api/bookings`

```json
{
  "room_id": 10,
  "start_date": "2026-12-23",
  "end_date": "2026-12-27",
  "guests": 2,
  "first_name": "Айдос",
  "last_name": "Нурланов",
  "email": "aidos@example.com",
  "country": "KZ",
  "phone": "+77001234567",
  "arrival_time": "10:00",
  "departure_time": "12:00",
  "services": [{ "service_id": 3, "quantity": 2 }]
}
```

`departure_time` необязателен, в ответе не возвращается — нужен только для расчёта. Ответ
(адреса, картинки и удобства сокращены):

```json
{
  "data": {
    "id": 101,
    "booking_number": "NS-2026-A1B2C3",
    "hotel": { "id": 1, "title": "Nomad Hotel", "city": "Алматы", "address": "…", "email": "…", "stars": 4, "lat": 43.2, "lng": 76.9, "main_image": "…" },
    "room": { "id": 10, "title": "Стандарт", "price": 10000, "beds": 1, "capacity": 2, "bathrooms": 1, "images": [], "amenities": [] },
    "start_date": "23.12.2026",
    "end_date": "27.12.2026",
    "guests": 2,
    "price_for_period": 70000.0,
    "price_breakdown": [
      { "date_from": "2026-12-23", "date_to": "2026-12-24", "nights": 2, "price_per_night": 10000, "amount": 20000, "rate_id": null, "rate_title": null },
      { "date_from": "2026-12-25", "date_to": "2026-12-26", "nights": 2, "price_per_night": 25000, "amount": 50000, "rate_id": 5, "rate_title": "Новогодние" }
    ],
    "early_check_in_amount": 5000.0,
    "late_check_out_amount": 0.0,
    "tax": 0.0,
    "services": [
      { "id": 41, "service": { "id": 3, "title": "Завтрак", "unit": "чел." }, "quantity": 2, "price": 3000, "amount": 6000, "comment": null }
    ],
    "services_amount": 6000.0,
    "total_price": 81000.0,
    "status": "booked",
    "guest": { "first_name": "Айдос", "last_name": "Нурланов", "email": "aidos@example.com", "country": "KZ", "phone": "+77001234567", "is_business_trip": false, "special_requests": null, "arrival_time": "10:00" }
  }
}
```

Если номер занят — `422` `{"message": "No rooms left for selected dates"}`.

### Админка — `POST /admin-api/bookings`

```json
{
  "room_id": 10,
  "start_date": "2026-12-23",
  "end_date": "2026-12-27",
  "guests": 2,
  "first_name": "Айдос",
  "last_name": "Нурланов",
  "phone": "+77001234567",
  "planned_check_in_time": "14:00",
  "planned_check_out_time": "12:00",
  "prepayment": { "amount": 20000, "method": "cash" }
}
```

Ответ `201`:

```json
{
  "message": "Бронирование создано",
  "data": {
    "id": 102,
    "booking_number": "NS-2026-D4E5F6",
    "status": "booked",
    "is_paid": false,
    "payment_status": "prepaid",
    "source": "manual",
    "type": "booking",
    "color": "#ffd97f",
    "start_date": "2026-12-23",
    "end_date": "2026-12-27",
    "nights": 4,
    "guests": 2,
    "price_for_period": 70000.0,
    "tax": 0.0,
    "early_check_in_amount": 0.0,
    "late_check_out_amount": 0.0,
    "services_amount": 0,
    "total_price": 70000.0,
    "paid_amount": 20000.0,
    "balance_due": 50000.0,
    "planned_check_in_at": "2026-12-23 14:00",
    "planned_check_out_at": "2026-12-27 12:00",
    "actual_check_in_at": null,
    "actual_check_out_at": null,
    "hotel": { "id": 1, "title": "Nomad Hotel" },
    "room": { "id": 10, "title": "Стандарт" },
    "guest": { "first_name": "Айдос", "last_name": "Нурланов", "email": null, "phone": "+77001234567", "country": null, "is_business_trip": false, "special_requests": null, "arrival_time": null },
    "created_at": "23.11.2026 13:50",
    "price_breakdown": [
      { "date_from": "2026-12-23", "date_to": "2026-12-24", "nights": 2, "price_per_night": 10000, "amount": 20000, "rate_id": null, "rate_title": null },
      { "date_from": "2026-12-25", "date_to": "2026-12-26", "nights": 2, "price_per_night": 25000, "amount": 50000, "rate_id": 5, "rate_title": "Новогодние" }
    ],
    "user": null,
    "payments": [
      { "id": 9, "amount": 20000.0, "method": "cash", "status": "completed", "paid_at": "2026-11-23 13:50" }
    ]
  }
}
```

---

## 4. Просмотр и изменение брони

- `GET /admin-api/bookings/102` — формат как в ответе на создание; дополнительно `services`, если в брони есть услуги.
  `price_breakdown` — снимок на момент создания, правка тарифов его не меняет.
- `GET /admin-api/bookings` (список) — тот же формат без `price_breakdown`, `user`, `services`, `payments`.
- `GET /api/bookings/101` — формат как в ответе на создание с сайта; `tax` и `total_price` приходят строками (`"0.00"`, `"81000.00"`).

### Изменение дат в админке — `PATCH /admin-api/bookings/102`

Пример: продлить до 28 декабря, при этом тариф с тех пор поднялся до 28 000. Тело: `{ "end_date": "2026-12-28" }`.
Ответ — полная бронь, ниже только изменившиеся поля:

```json
{
  "message": "Бронирование обновлено",
  "data": {
    "end_date": "2026-12-28",
    "nights": 5,
    "price_for_period": 95000.0,
    "total_price": 95000.0,
    "balance_due": 75000.0,
    "price_breakdown": [
      { "date_from": "2026-12-23", "date_to": "2026-12-24", "nights": 2, "price_per_night": 10000, "amount": 20000, "rate_id": null, "rate_title": null },
      { "date_from": "2026-12-25", "date_to": "2026-12-27", "nights": 3, "price_per_night": 25000, "amount": 75000, "rate_id": 5, "rate_title": "Новогодние" }
    ]
  }
}
```

- Ночи, которые уже были в брони, остаются по прежней цене из снимка.
- Новая ночь 27 декабря считается по тарифу, действующему сейчас.
- Если менять только время (`planned_check_in_time`, `planned_check_out_time`), стоимость проживания не меняется —
  пересчитываются только доплаты за ранний заезд / поздний выезд.

### Изменение дат с сайта — `PATCH /api/bookings/101/dates`

Тело: `{ "start_date": "2026-12-23", "end_date": "2026-12-28" }`. Ответ — формат как у `GET /api/bookings/{id}`
с обновлёнными суммами и `price_breakdown`. Если номер занят — `422` `{"message": "Номер недоступен на выбранные даты"}`.

---

## 5. Карточка номера для гостя

`GET /api/rooms/10?start_date=2026-12-23&end_date=2026-12-27&guests=2`

Если даты переданы, рядом с базовой `price` приходит цена проживания на эти даты. Без дат этих полей нет.

```json
{
  "data": {
    "id": 10,
    "title": "Стандарт",
    "price": 10000,
    "nights": 4,
    "price_for_period": 70000,
    "price_breakdown": [
      { "date_from": "2026-12-23", "date_to": "2026-12-24", "nights": 2, "price_per_night": 10000, "amount": 20000, "rate_id": null, "rate_title": null },
      { "date_from": "2026-12-25", "date_to": "2026-12-26", "nights": 2, "price_per_night": 25000, "amount": 50000, "rate_id": 5, "rate_title": "Новогодние" }
    ],
    "beds": 1,
    "capacity": 2,
    "bathrooms": 1,
    "stock": 3,
    "images": [],
    "amenities": []
  }
}
```

То же самое попадает в номера внутри `GET /api/hotels/{id}`.
