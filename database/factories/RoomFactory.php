<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->paragraph(),
            'capacity' => $this->faker->numberBetween(2, 50),
            'floor' => (string) $this->faker->numberBetween(1, 10),
            'amenities' => json_encode($this->faker->randomElements(
                ['projector', 'whiteboard', 'wifi', 'tv', 'conference-call', 'catering'], 
                $this->faker->numberBetween(2, 4)
            )),
            'is_active' => true,
        ];
    }
}