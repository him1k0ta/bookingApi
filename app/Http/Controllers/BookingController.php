<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Booking::with(['room', 'user']);

        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $bookings = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $overlapping = Booking::overlapping(
            $request->room_id,
            $request->date,
            $request->start_time,
            $request->end_time
        )->exists();

        if ($overlapping) {
            return response()->json([
                'error' => 'This time slot is already booked',
            ], 409);
        }

        $booking = Booking::create([
            'user_id' => auth()->id(),
            'room_id' => $request->room_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'notes' => $request->notes,
            'status' => 'active',
        ]);

        return response()->json($booking->load('room'), 201);
    }

    public function show(Booking $booking)
    {
        $user = auth()->user();

        if (!$user->isAdmin() && $booking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($booking->load(['room', 'user', 'review']));
    }

    public function cancel(Booking $booking)
    {
        $user = auth()->user();

        if (!$user->isAdmin() && $booking->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($booking->status === 'cancelled') {
            return response()->json(['error' => 'Booking already cancelled'], 400);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking cancelled successfully']);
    }

    public function userBookings()
    {
        $bookings = auth()->user()->bookings()
            ->with('room')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        return response()->json($bookings);
    }
}
