<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $booking = Booking::findOrFail($request->booking_id);
        
        // Проверка, что бронирование принадлежит пользователю
        if ($booking->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Проверка, что бронирование завершено
        if ($booking->date > now()->toDateString() || 
            ($booking->date == now()->toDateString() && $booking->end_time > now()->format('H:i'))) {
            return response()->json(['error' => 'Cannot review upcoming booking'], 400);
        }

        // Проверка, что отзыв еще не оставлен
        if ($booking->review()->exists()) {
            return response()->json(['error' => 'Review already exists for this booking'], 400);
        }

        $review = Review::create([
            'booking_id' => $booking->id,
            'user_id' => auth()->id(),
            'room_id' => $booking->room_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json($review, 201);
    }

    public function update(Request $request, Review $review)
    {
        if ($review->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'sometimes|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $review->update($request->only(['rating', 'comment']));
        
        return response()->json($review);
    }

    public function destroy(Review $review)
    {
        if ($review->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review->delete();
        
        return response()->json(['message' => 'Review deleted successfully']);
    }
}