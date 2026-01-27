<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Camera;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /**
     * USER membuat rental
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cameras_id' => 'required|exists:cameras,id',
            'start_date' => 'required|date|after_or_equal:today',
            'due_date' => 'required|date|after:start_date'
        ]);

        $camera = Camera::findOrFail($validated['cameras_id']);

        if ($camera->stock < 1) {
            return response()->json(['message' => 'Stok kamera habis'], 400);
        }

        // LOGIKA HITUNG HARGA
        $start = Carbon::parse($validated['start_date']);
        $due = Carbon::parse($validated['due_date']);
        $days = $start->diffInDays($due);   
        $days = $days < 1 ? 1 : $days; // Minimal 1 hari
        $totalPrice = $days * $camera->price_per_day;

        return DB::transaction(function () use ($validated, $camera, $totalPrice, $days) {
            $camera->decrement('stock');

            $rental = Rental::create([
                'user_id' => auth()->id(),
                'cameras_id' => $camera->id,
                'start_date' => $validated['start_date'],
                'due_date' => $validated['due_date'],
                'total_price' => $totalPrice,
                'status' => 'pending'
            ]);

            return response()->json([
                'message' => 'Rental berhasil dipesan. Silahkan lakukan pembayaran.',
                'total_days' => $days,
                'total_price' => $totalPrice,
                'rental' => $rental
            ], 201);
        });
    }

    // ADMIN: Menyetujui rental (setelah cek pembayaran)
    public function approve(Rental $rental)
    {
        if (auth()->user()->role !== 'admin') return response()->json(['message' => 'Forbidden'], 403);

        // Pastikan sudah bayar dulu sebelum diambil
        if (!$rental->payment || $rental->payment->status !== 'paid') {
            return response()->json(['message' => 'User belum menyelesaikan pembayaran!'], 400);
        }

        $rental->update(['status' => 'approved']);

        return response()->json(['message' => 'Status Updated: Kamera telah diserahkan kepada user.']);
    }

    // ADMIN/USER: Mengembalikan kamera
    public function returnCamera(Rental $rental)
    {
        if ($rental->status !== 'approved') {
            return response()->json(['message' => 'Status rental tidak valid untuk pengembalian'], 400);
        }

        DB::transaction(function () use ($rental) {
            $rental->camera->increment('stock');
            $rental->update(['status' => 'returned']);
        });

        return response()->json(['message' => 'Kamera berhasil dikembalikan, stok bertambah']);
    }
}
