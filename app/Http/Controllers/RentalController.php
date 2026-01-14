<?php

namespace App\Http\Controllers;

use App\Models\Rental;
use App\Models\Camera;
use Illuminate\Http\Request;

class RentalController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'cameras_id' => 'required|exists:cameras,id',
            'start_date' => 'required|data',
            'due_date' => 'required|date|after:start_daate'
        ]);

        $camera = Camera::with('store')->findOrFail($request->cameras_id);

        if($camera->store->user_id === auth()->id()){
            return response()->json([
                'message' => 'Anda tidak bisa meminjam barang sendiri'
            ],403);
        }

        $rental = Rental::create([
            'user_id' => auth()->id(),
            'cameras_id' => $camera->id,
            'start_date' => $request->start_date,
            'due_date' => $request->due_date,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Rental berhasil dibuat',
            'rental' => $rental
        ],201);
    }

    //function buat liat peminjaman atau rental cameran miliknya
    public function storeRentals()
    {
        $rentals = Rental::whereHas('camera.store', function($q){
            $q->where('user_id', auth()->id());
        })->with(['camera', 'user'])->latest()->get();
    }

    public function approve(Rental $rental)
    {
        if ($rental->camera->store->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rental->update(['status' => 'approved']);

        return response()->json(['message' => 'Rental disetujui']);
    }

    public function reject(Rental $rental)
    {
        if ($rental->camera->store->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $rental->update(['status' => 'rejected']);

        return response()->json(['message' => 'Rental ditolak']);
    }

    public function return(Rental $rental)
    {
        if ($rental->user_id !== auth()->id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $status = now()->gt($rental->due_date) ? 'late' : 'returned';

        $rental->update([
            'returned_at' => now(),
            'status' => $status
        ]);

        return response()->json([
            'message' => 'Camera berhasil dikembalikan',
            'status' => $status
        ]);
    }

}
