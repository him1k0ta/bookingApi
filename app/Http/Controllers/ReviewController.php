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
        
        // бронирование принадлежит пользователю
        if ($booking->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // бронирование завершено
        if ($booking->date > now()->toDateString() || 
            ($booking->date == now()->toDateString() && $booking->end_time > now()->format('H:i'))) {
            return response()->json(['error' => 'Cannot review upcoming booking'], 400);
        }

        // отзыв еще не оставлен
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
     public function roomReviews(Room $room, Request $request)
    {
        $reviews = $room->reviews()
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        $stats = [
            'average_rating' => $room->reviews()->avg('rating') ?? 0,
            'total_reviews' => $room->reviews()->count(),
            'rating_distribution' => [
                1 => $room->reviews()->where('rating', 1)->count(),
                2 => $room->reviews()->where('rating', 2)->count(),
                3 => $room->reviews()->where('rating', 3)->count(),
                4 => $room->reviews()->where('rating', 4)->count(),
                5 => $room->reviews()->where('rating', 5)->count(),
            ],
        ];

        return response()->json([
            'data' => $reviews->items(),
            'stats' => $stats,
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function userReviews(Request $request)
    {
        $reviews = auth()->user()->reviews()
            ->with(['room:id,name', 'booking:id,date,start_time,end_time'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function canReview(Booking $booking)
    {
        $user = auth()->user();

        if ($booking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $canReview = !$booking->review()->exists() 
            && ($booking->date < now()->toDateString() 
                || ($booking->date == now()->toDateString() && $booking->end_time < now()->format('H:i')));

        $message = '';
        if (!$canReview) {
            if ($booking->review()->exists()) {
                $message = 'Review already exists';
            } else {
                $message = 'Booking is not completed yet';
            }
        }

        return response()->json([
            'can_review' => $canReview,
            'message' => $message,
            'booking' => $booking->only(['id', 'date', 'start_time', 'end_time', 'status']),
        ]);
    }
}