<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class RoomFilterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
        
        // Create test rooms
        Room::factory()->create([
            'name' => 'Conference Room A',
            'capacity' => 10,
            'floor' => '1',
            'amenities' => ['projector', 'whiteboard'],
            'is_active' => true,
        ]);
        
        Room::factory()->create([
            'name' => 'Meeting Room B',
            'capacity' => 6,
            'floor' => '2',
            'amenities' => ['tv', 'whiteboard'],
            'is_active' => true,
        ]);
        
        Room::factory()->create([
            'name' => 'Large Hall',
            'capacity' => 50,
            'floor' => '1',
            'amenities' => ['projector', 'sound_system'],
            'is_active' => true,
        ]);
        
        Room::factory()->create([
            'name' => 'Small Room',
            'capacity' => 4,
            'floor' => '3',
            'amenities' => ['whiteboard'],
            'is_active' => true,
        ]);
    }

    public function test_can_filter_rooms_by_capacity(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?capacity=10');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
        
        $capacities = collect($response->json('data'))->pluck('capacity');
        $this->assertTrue($capacities->every(fn($cap) => $cap >= 10));
    }

    public function test_can_filter_rooms_by_floor(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?floor=2');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.floor', '2');
    }

    public function test_can_filter_rooms_by_amenities(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?amenities=projector');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
        
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Conference Room A'));
        $this->assertTrue($names->contains('Large Hall'));
    }

    public function test_can_search_rooms(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?search=Conference');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Conference Room A');
    }

    public function test_can_filter_available_rooms_by_time(): void
    {
        // Get first room
        $room = Room::first();
        
        // Create a booking for tomorrow at 10-12
        Booking::create([
            'user_id' => $this->user->id,
            'room_id' => $room->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'status' => 'active',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?' . http_build_query([
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]));

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data'); // All rooms except the booked one
        
        $roomIds = collect($response->json('data'))->pluck('id');
        $this->assertFalse($roomIds->contains($room->id));
    }

    public function test_can_filter_by_min_rating(): void
    {
        // Get first room
        $room = Room::first();
        
        // Create reviews with high rating for this room
        Review::factory()->count(3)->create([
            'room_id' => $room->id,
            'rating' => 5,
        ]);
        
        // Get second room
        $room2 = Room::where('id', '!=', $room->id)->first();
        
        // Create reviews with low rating for this room
        Review::factory()->count(2)->create([
            'room_id' => $room2->id,
            'rating' => 2,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?min_rating=4');

        $response->assertStatus(200);
        
        $ratings = collect($response->json('data'))->pluck('average_rating');
        $this->assertTrue($ratings->every(fn($rating) => $rating >= 4));
    }

    public function test_pagination_works(): void
    {
        //pagination
        Room::factory()->count(20)->create(['is_active' => true]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.current_page', 1);
    }

    public function test_can_sort_rooms(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/rooms?sort_by=capacity&sort_order=desc');

        $response->assertStatus(200);
        
        $capacities = collect($response->json('data'))->pluck('capacity');
        $sortedCapacities = $capacities->sortDesc()->values();
        
        $this->assertEquals($sortedCapacities->toArray(), $capacities->toArray());
    }
}