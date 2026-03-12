<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::where('is_active', true);

        // Фильтрация
        if ($request->has('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        if ($request->has('floor')) {
            $query->where('floor', $request->floor);
        }

        if ($request->has('amenities')) {
            $amenities = explode(',', $request->amenities);
            foreach ($amenities as $amenity) {
                $query->whereJsonContains('amenities', trim($amenity));
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('min_rating')) {
            $minRating = $request->min_rating;
            $query->whereHas('reviews', function($q) use ($minRating) {
                $q->select('room_id')
                  ->groupBy('room_id')
                  ->havingRaw('avg(rating) >= ?', [$minRating]);
            });
        }

        // Поиск свободных комнат на конкретное время
        if ($request->has('date') && $request->has('start_time') && $request->has('end_time')) {
            $bookedRoomIds = Booking::where('date', $request->date)
                ->where('status', 'active')
                ->where(function ($q) use ($request) {
                    $q->whereBetween('start_time', [$request->start_time, $request->end_time])
                      ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                      ->orWhere(function ($q2) use ($request) {
                          $q2->where('start_time', '<=', $request->start_time)
                             ->where('end_time', '>=', $request->end_time);
                      });
                })
                ->pluck('room_id');

            $query->whereNotIn('id', $bookedRoomIds);
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        
        if ($sortBy === 'rating') {
            $query->withAvg('reviews', 'rating')
                  ->orderBy('reviews_avg_rating', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 15);
        $rooms = $query->with('reviews')->paginate($perPage);

        // Добавляем средний рейтинг к каждой комнате
        $rooms->getCollection()->transform(function ($room) {
            $room->average_rating = $room->averageRating();
            $room->total_reviews = $room->reviews()->count();
            return $room;
        });

        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1',
            'floor' => 'nullable|string',
            'amenities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $room = Room::create($request->all());
        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        $room->load('reviews');
        $room->average_rating = $room->averageRating();
        $room->total_reviews = $room->reviews()->count();
        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'floor' => 'nullable|string',
            'amenities' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $room->update($request->all());
        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $room->delete();
        return response()->json(['message' => 'Room deleted successfully']);
    }

    public function schedule(Room $room, Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'period' => 'in:day,week'
        ]);

        $date = $request->date;
        $period = $request->period ?? 'day';

        $query = $room->bookings()
            ->with('user:id,name')
            ->where('status', 'active')
            ->where('date', '>=', $date);

        if ($period === 'day') {
            $query->where('date', $date);
        } else {
            $endDate = date('Y-m-d', strtotime($date . ' +7 days'));
            $query->where('date', '<=', $endDate);
        }

        $bookings = $query->orderBy('date')->orderBy('start_time')->get();

        return response()->json([
            'room' => $room->only(['id', 'name', 'capacity']),
            'date' => $date,
            'period' => $period,
            'bookings' => $bookings
        ]);
    }
}