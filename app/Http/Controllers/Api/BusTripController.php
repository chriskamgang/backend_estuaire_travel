<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusTrip;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BusTripController extends Controller
{
    /**
     * Get list of bus trips
     */
    public function index(Request $request)
    {
        $query = BusTrip::with(['company', 'fromCity', 'toCity'])
            ->where('status', 'active')
            ->orderBy('departure_time', 'asc');

        // Filter by date
        if ($request->has('date')) {
            $date = Carbon::parse($request->date)->format('Y-m-d');
            $query->whereDate('departure_time', $date);
        }

        $trips = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $trips,
        ]);
    }

    /**
     * Search bus trips
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'from_city_id' => 'required|exists:cities,id',
            'to_city_id' => 'required|exists:cities,id',
            'date' => 'required|date',
            'passengers' => 'sometimes|integer|min:1',
        ]);

        $date = Carbon::parse($validated['date'])->format('Y-m-d');
        $passengers = $validated['passengers'] ?? 1;
        $isToday = $date === Carbon::now()->setTimezone(config('app.timezone'))->format('Y-m-d');
        $nowTime  = Carbon::now()->setTimezone(config('app.timezone'))->format('H:i:s');

        // Nom du jour en anglais minuscule ex: "monday", "tuesday"...
        $dayName = strtolower(Carbon::parse($date)->englishDayOfWeek);

        $trips = BusTrip::with(['company', 'fromCity', 'toCity'])
            ->where('from_city_id', $validated['from_city_id'])
            ->where('to_city_id', $validated['to_city_id'])
            ->where('status', 'active')
            ->where(function ($query) use ($date, $dayName) {
                // Trajets récurrents dont ce jour est dans recurring_days
                $query->where('recurring', true)
                      ->whereJsonContains('recurring_days', $dayName);
            })
            // Si la date recherchée est aujourd'hui, exclure les bus déjà partis
            ->when($isToday, function ($query) use ($nowTime) {
                $query->where('departure_time', '>', $nowTime);
            })
            ->whereRaw('(total_seats - (SELECT COALESCE(SUM(number_of_seats), 0) FROM bookings WHERE bookings.bus_trip_id = bus_trips.id AND bookings.status != "cancelled")) >= ?', [$passengers])
            ->orderBy('departure_time', 'asc')
            ->get()
            ->map(function ($trip) {
                $bookedSeats = $trip->bookings()->where('status', '!=', 'cancelled')->sum('number_of_seats');
                $trip->available_seats = $trip->total_seats - $bookedSeats;
                return $trip;
            });

        return response()->json([
            'success' => true,
            'data' => $trips,
            'search_params' => [
                'from_city_id' => $validated['from_city_id'],
                'to_city_id' => $validated['to_city_id'],
                'date' => $date,
                'passengers' => $passengers,
            ],
        ]);
    }

    /**
     * Get bus trip details
     */
    public function show(string $id)
    {
        $trip = BusTrip::with(['company', 'fromCity', 'toCity', 'bookings'])
            ->findOrFail($id);

        $bookedSeats = $trip->bookings()->where('status', '!=', 'cancelled')->sum('number_of_seats');
        $trip->available_seats = $trip->total_seats - $bookedSeats;
        $trip->booked_seats = $bookedSeats;

        return response()->json([
            'success' => true,
            'data' => $trip,
        ]);
    }

    /**
     * Get booked seats for a specific trip and date
     */
    public function getBookedSeats(string $id, Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        $trip = BusTrip::findOrFail($id);

        // Get all bookings for this trip on this date
        $bookings = $trip->bookings()
            ->where('travel_date', $validated['date'])
            ->where('status', '!=', 'cancelled')
            ->get();

        // Extract all booked seat numbers from the 'seats' JSON field
        $bookedSeats = [];
        foreach ($bookings as $booking) {
            if ($booking->seats && is_array($booking->seats)) {
                $bookedSeats = array_merge($bookedSeats, $booking->seats);
            }
        }

        // Remove duplicates and sort
        $bookedSeats = array_values(array_unique($bookedSeats));
        sort($bookedSeats);

        return response()->json([
            'success' => true,
            'data' => [
                'trip_id' => $trip->id,
                'date' => $validated['date'],
                'total_seats' => $trip->total_seats,
                'booked_seats' => $bookedSeats,
                'available_seats' => $trip->total_seats - count($bookedSeats),
            ],
        ]);
    }
}
