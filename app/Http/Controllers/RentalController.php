<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Camera;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    /**
     * USER: rental miliknya
     * ADMIN: semua rental
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $rentals = Rental::with(['camera.store', 'user', 'payment'])->get();
        } else {
            $rentals = Rental::where('user_id', $user->id)->with(['camera.store', 'payment'])->get();
        }

        return response()->json($rentals);
    }

    /**
     * USER membuat rental
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'user') {
            return response()->json([
                'message' => 'Hanya user yang dapat membuat rental'
            ], 403);
        }

        $validated = $request->validate([
            'camera_id' => 'required|exists:cameras,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after:start_date'
        ]);

        $camera = Camera::findOrFail($validated['camera_id']);

        if ($camera->stock < 1) {
            return response()->json([
                'message' => 'Stok kamera habis'
            ], 400);
        }

        $rental = Rental::create([
            'user_id' => auth()->id(),
            'camera_id' => $camera->id,
            'start_date' => $validated['start_date'],
            'due_date' => $validated['due_date'],
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Rental berhasil dibuat',
            'rental' => $rental
        ], 201);
    }

    /**
     * Detail rental
     */
    public function show(Rental $rental)
    {
        $user = auth()->user();

        if (
            $user->role !== 'admin' &&
            $rental->user_id !== $user->id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(
            $rental->load(['camera.store', 'payment'])
        );
    }
}
