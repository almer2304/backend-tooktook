<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * USER: payment miliknya
     * ADMIN: semua payment
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            $payments = Payment::with('rental.user')->get();
        } else {
            $payments = Payment::whereHas('rental', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with('rental')->get();
        }

        return response()->json($payments);
    }

    /**
     * USER membuat payment
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'user') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rental_id' => 'required|exists:rentals,id',
            'method' => 'required|string'
        ]);

        $rental = Rental::where('id', $validated['rental_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (!$rental) {
            return response()->json([
                'message' => 'Rental tidak ditemukan'
            ], 403);
        }

        if ($rental->payment) {
            return response()->json([
                'message' => 'Payment sudah ada'
            ], 400);
        }

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'amount' => $rental->total_price ?? 0,
            'method' => $validated['method'],
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Payment dibuat',
            'payment' => $payment
        ], 201);
    }

    /**
     * USER membayar
     */
    public function pay(Payment $payment)
    {
        if (
            auth()->user()->role !== 'user' ||
            $payment->rental->user_id !== auth()->id()
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Sudah dibayar'
            ], 400);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return response()->json([
            'message' => 'Payment sukses',
            'payment' => $payment
        ]);
    }
}
