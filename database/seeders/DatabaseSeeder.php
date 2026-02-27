<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Создаем админа
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Создаем обычных пользователей
        $users = User::factory(10)->create();

        // Создаем комнаты
        $rooms = [];
        $amenitiesList = [
            ['projector', 'whiteboard', 'wifi'],
            ['wifi', 'tv', 'conference-call'],
            ['whiteboard', 'wifi'],
            ['projector', 'wifi', 'catering'],
            ['wifi', 'tv'],
        ];

        for ($i = 1; $i <= 10; $i++) {
            $rooms[] = Room::create([
                'name' => "Room {$i}",
                'description' => "Description for Room {$i}",
                'capacity' => rand(2, 20),
                'floor' => rand(1, 5),
                'amenities' => $amenitiesList[array_rand($amenitiesList)],
                'is_active' => true,
            ]);
        }

        // Создаем бронирования
        $statuses = ['active', 'completed', 'cancelled'];
        
        foreach ($users as $user) {
            foreach (range(1, 5) as $j) {
                $room = $rooms[array_rand($rooms)];
                $date = now()->addDays(rand(-10, 20));
                $startHour = rand(9, 17);
                $endHour = $startHour + rand(1, 3);
                
                $booking = Booking::create([
                    'user_id' => $user->id,
                    'room_id' => $room->id,
                    'date' => $date,
                    'start_time' => "{$startHour}:00",
                    'end_time' => "{$endHour}:00",
                    'status' => $statuses[array_rand($statuses)],
                    'notes' => "Booking notes {$j}",
                ]);

                // Создаем отзывы для завершенных бронирований
                if ($booking->status === 'completed' && rand(0, 1)) {
                    Review::create([
                        'booking_id' => $booking->id,
                        'user_id' => $user->id,
                        'room_id' => $room->id,
                        'rating' => rand(3, 5),
                        'comment' => "Review comment for booking {$booking->id}",
                    ]);
                }
            }
        }
    }
}