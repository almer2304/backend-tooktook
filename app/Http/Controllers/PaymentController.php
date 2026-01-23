<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rental_id' => 'required|exists:rentals,id',
            'method' => 'required|string'
        ]);

        $rental = Rental::where('id', $validated['rental_id'])
                        ->where('user_id', auth()->id())
                        ->firstOrFail();

        if ($rental->payment) {
            return response()->json(['message' => 'Payment sudah ada'], 400);
        }

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'amount' => $rental->total_price, // Diambil dari hasil hitung di RentalController
            'method' => $validated['method'],
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Tagihan pembayaran dibuat', 'payment' => $payment], 201);
    }

    public function pay(Payment $payment)
    {
        // Pastikan hanya owner yang bisa bayar
        if ($payment->rental->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return response()->json(['message' => 'Pembayaran sukses, menunggu verifikasi admin']);
    }
}