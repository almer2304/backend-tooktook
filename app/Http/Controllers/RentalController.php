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
            'cameras_id' => 'required|exists:cameras,id',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after:start_date'
        ]);

        $camera = Camera::findOrFail($validated['cameras_id']);

        if ($camera->stock < 1) {
            return response()->json([
                'message' => 'Stok kamera habis'
            ], 400);
        }

        $camera->decrement('stock');

        $rental = Rental::create([
            'user_id' => auth()->id(),
            'cameras_id' => $camera->id,
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

    public function approve(Rental $rental)
    {
        if ($rental->status !== 'pending') {
            return response()->json(['message' => 'Hanya rental pending yang bisa disetujui'], 400);
        }

        // Update status menjadi approved
        $rental->update(['status' => 'approved']);

        return response()->json(['message' => 'Rental telah disetujui']);
    }

    public function returnCamera(Rental $rental)
    {
        // Pastikan hanya rental yang 'approved' yang bisa dikembalikan
        if ($rental->status !== 'approved') {
            return response()->json(['message' => 'Kamera belum disetujui atau sudah dikembalikan'], 400);
        }

        \DB::transaction(function () use ($rental) {
            // 1. Tambah stok kamera kembali
            $rental->camera->increment('stock');

            // 2. Ubah status rental
            $rental->update(['status' => 'returned']);
        });

        return response()->json([
            'message' => 'Kamera berhasil dikembalikan, stok telah diperbarui',
            'rental' => $rental->load('camera')
        ]);
    }

    public function reject(Rental $rental)
    {
        if ($rental->status === 'pending') {
            \DB::transaction(function () use ($rental) {
                $rental->camera->increment('stock'); // Kembalikan stok
                $rental->update(['status' => 'rejected']);
            });
            return response()->json(['message' => 'Rental ditolak, stok dikembalikan']);
        }
    }
}
