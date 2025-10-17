<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\City;
use App\Models\Country;
use App\Models\Hotel;
use App\Models\HotelImage;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Country::insert([
            ['name' => 'Казахстан'],
            ['name' => 'Польша'],
            ['name' => 'США'],
        ]);
        $cities = [
            ['country_id' => 1, 'name' => 'Алматы'],
            ['country_id' => 1, 'name' => 'Астана'],
            ['country_id' => 2, 'name' => 'Варшава'],
            ['country_id' => 2, 'name' => 'Краков'],
            ['country_id' => 3, 'name' => 'Лос Анжелес']
        ];
        City::insert($cities);
        $amenities = [
            'Кондиционер',
            'Бесплатный Wi-Fi',
            'Бассейн',
            'Парковка',
            'Фитнес-центр',
            'Завтрак включен',
            'Спа и сауна',
            'Бар / Ресторан',
            'Обслуживание номеров',
            'Телевизор с Netflix'
        ];
        foreach ($amenities as $name) {
            Amenity::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
        $hotels = [
            [
                'title' => 'Moxy Warsaw City',
                'city' => 'Warsaw',
                'country' => 'Poland',
                'address' => 'ul. Żelazna 51/53, Wola, 00-841 Warsaw',
                'stars' => 4,
                'latitude' => 52.2319,
                'longitude' => 21.0067,
                'description' => 'Современный стильный отель в центре Варшавы. Отличный выбор для путешественников.',
                'min_price' => 321412,
                'type' => 'hotel',
                'is_active' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1600585154340-be6161a56a0c',
                    'https://images.unsplash.com/photo-1600585154608-275dc24a1296',
                    'https://images.unsplash.com/photo-1600585154204-527bdc8fa283',
                ],
            ],
            [
                'title' => 'Royal Apartments Los Angeles',
                'city' => 'Los Angeles',
                'country' => 'USA',
                'address' => '123 Hollywood Blvd',
                'stars' => 5,
                'latitude' => 34.0522,
                'longitude' => -118.2437,
                'description' => 'Роскошные апартаменты в самом центре Голливуда с панорамным видом на город.',
                'min_price' => 450000,
                'type' => 'apartment',
                'is_active' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1600585154356-596af9009f5a',
                    'https://images.unsplash.com/photo-1600585154456-03a5c5c72c1c',
                ],
            ],
            [
                'title' => 'Hostel Nomad Almaty',
                'city' => 'Almaty',
                'country' => 'Kazakhstan',
                'address' => 'ул. Абая 35',
                'stars' => 3,
                'latitude' => 43.238949,
                'longitude' => 76.889709,
                'description' => 'Уютный хостел в центре Алматы. Отличное соотношение цены и качества.',
                'min_price' => 80000,
                'type' => 'hostel',
                'is_active' => true,
                'images' => [
                    'https://images.unsplash.com/photo-1600585154805-0c7c65c56d1d',
                    'https://images.unsplash.com/photo-1600585154213-3a9a6e0b3c5b',
                ],
            ],
        ];
        foreach ($hotels as $data) {
            $hotel = Hotel::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                collect($data)->except('images')->toArray()
            );
            foreach ($data['images'] as $index => $path) {
                HotelImage::updateOrCreate([
                    'hotel_id' => $hotel->id,
                    'path' => $path,
                    'is_main' => $index === 0,
                ]);
            }
            $amenityIds = Amenity::inRandomOrder()->take(rand(3, 6))->pluck('id');
            $hotel->amenities()->sync($amenityIds);
        }
        $hotels = Hotel::all();
        foreach ($hotels as $hotel) {
            for ($i = 1; $i <= 3; $i++) {
                $room = $hotel->rooms()->create([
                    'title' => "Номер #$i — {$hotel->title}",
                    'description' => 'Просторный номер с видом на город. Удобства включают кондиционер, Wi-Fi и мини-бар.',
                    'capacity' => rand(2, 4),
                    'price' => $hotel->min_price + rand(20000, 100000),
                    'stock' => rand(2, 10),
                ]);
                $room->images()->create([
                    'path' => 'https://images.unsplash.com/photo-1600585154204-527bdc8fa283',
                    'is_main' => true,
                ]);
            }
        }
    }
}
