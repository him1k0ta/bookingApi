<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Room;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;

class BookingTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user can create a booking
     */
    public function test_user_can_create_booking(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var Room $room */
        $room = Room::factory()->create();

        $bookingData = [
            'room_id' => $room->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'notes' => $this->faker->sentence(),
        ];

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/bookings', $bookingData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'room_id',
                     'user_id',
                     'date',
                     'start_time',
                     'end_time',
                     'status',
                     'notes',
                     'room'
                 ]);

        // Проверяем что статус 'active'
        $this->assertEquals('active', $response->json('status'));
    }

    /**
     * Test cannot create overlapping booking
     */
    public function test_cannot_create_overlapping_booking(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var Room $room */
        $room = Room::factory()->create();

        /** @var Booking $firstBooking */
        $firstBooking = Booking::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'status' => 'active',
        ]);

        /** @var TestResponse $response */
        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/bookings', [
                             'room_id' => $room->id,
                             'date' => now()->addDay()->format('Y-m-d'),
                             'start_time' => '11:00',
                             'end_time' => '13:00',
                         ]);

        $response->assertStatus(409)
                 ->assertJson(['error' => 'This time slot is already booked']);
        
        $this->assertDatabaseCount('bookings', 1);
    }

    /**
     * Test unauthorized cannot create booking
     */
    public function test_unauthorized_cannot_create_booking(): void
    {
        /** @var Room $room */
        $room = Room::factory()->create();

        /** @var TestResponse $response */
        $response = $this->postJson('/api/bookings', [
            'room_id' => $room->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user can cancel own booking
     */
    public function test_user_can_cancel_own_booking(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var Booking $booking */
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        /** @var TestResponse $response */
        $response = $this->actingAs($user, 'api')
                         ->patchJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Booking cancelled successfully']);

        /** @var Booking $refreshedBooking */
        $refreshedBooking = $booking->fresh();
        $this->assertEquals('cancelled', $refreshedBooking->status);
    }

    /**
     * Test user cannot cancel others booking
     */
    public function test_user_cannot_cancel_others_booking(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        
        /** @var User $user2 */
        $user2 = User::factory()->create();
        
        /** @var Booking $booking */
        $booking = Booking::factory()->create([
            'user_id' => $user1->id,
            'status' => 'active'
        ]);

        /** @var TestResponse $response */
        $response = $this->actingAs($user2, 'api')
                         ->patchJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
        
        /** @var Booking $refreshedBooking */
        $refreshedBooking = $booking->fresh();
        $this->assertEquals('active', $refreshedBooking->status);
    }

    /**
     * Test admin can cancel any booking
     */
    public function test_admin_can_cancel_any_booking(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var Booking $booking */
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'status' => 'active'
        ]);

        /** @var TestResponse $response */
        $response = $this->actingAs($admin, 'api')
                         ->patchJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(200);

        /** @var Booking $refreshedBooking */
        $refreshedBooking = $booking->fresh();
        $this->assertEquals('cancelled', $refreshedBooking->status);
    }

    /**
     * Test user can view own bookings
     */
    public function test_user_can_view_own_bookings(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var \Illuminate\Database\Eloquent\Collection $userBookings */
        $userBookings = Booking::factory()->count(3)->create([
            'user_id' => $user->id
        ]);
        
        /** @var User $otherUser */
        $otherUser = User::factory()->create();
        
        /** @var Booking $otherBooking */
        $otherBooking = Booking::factory()->create([
            'user_id' => $otherUser->id
        ]);

        /** @var TestResponse $response */
        $response = $this->actingAs($user, 'api')
                         ->getJson('/api/my-bookings');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'current_page',
                     'data' => [
                         '*' => ['id', 'room_id', 'user_id', 'date', 'start_time', 'end_time', 'status']
                     ]
                 ]);
            
        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test booking validation
     */
    public function test_booking_validation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        // Тест с несуществующей комнатой
        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/bookings', [
                             'room_id' => 99999,
                             'date' => now()->addDay()->format('Y-m-d'),
                             'start_time' => '10:00',
                             'end_time' => '12:00',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['room_id']);

        // Тест с прошедшей датой
        $room = Room::factory()->create();
        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/bookings', [
                             'room_id' => $room->id,
                             'date' => now()->subDay()->format('Y-m-d'),
                             'start_time' => '10:00',
                             'end_time' => '12:00',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);

        // Тест с end_time раньше start_time
        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/bookings', [
                             'room_id' => $room->id,
                             'date' => now()->addDay()->format('Y-m-d'),
                             'start_time' => '14:00',
                             'end_time' => '12:00',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_time']);
    }

    /**
     * Test admin can view all bookings
     */
    public function test_admin_can_view_all_bookings(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        
        /** @var User $user1 */
        $user1 = User::factory()->create();
        
        /** @var User $user2 */
        $user2 = User::factory()->create();
        
        // Create bookings for different users
        Booking::factory()->count(2)->create(['user_id' => $user1->id]);
        Booking::factory()->count(3)->create(['user_id' => $user2->id]);

        /** @var TestResponse $response */
        $response = $this->actingAs($admin, 'api')
                         ->getJson('/api/bookings');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'current_page',
                     'data' => [
                         '*' => ['id', 'room_id', 'user_id', 'date', 'start_time', 'end_time', 'status']
                     ]
                 ]);
        
        // Admin should see all 5 bookings
        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test cannot book a room in the past
     */
    public function test_cannot_book_past_date(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        
        /** @var Room $room */
        $room = Room::factory()->create();

        $response = $this->actingAs($user, 'api')
                         ->postJson('/api/bookings', [
                             'room_id' => $room->id,
                             'date' => now()->subDay()->format('Y-m-d'),
                             'start_time' => '10:00',
                             'end_time' => '12:00',
                         ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['date']);
    }
}