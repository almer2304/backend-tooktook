<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay(Request $request, Rental $rental)
    {
        if ($rental->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($rental->payment && $rental->payment->status === 'paid') {
            return response()->json(['message' => 'Sudah dibayar'], 400);
        }

        $request->validate([
            'method' => 'required|in:cash,qris,debit'
        ]);

        $payment = $rental->payment()->exists()
            ? $rental->payment
            : Payment::create([
                'rental_id' => $rental->id,
                'amount' => $rental->total_price,
                'method' => $request->method,
                'status' => 'paid',
                'paid_at' => now()
            ]);


        return response()->json([
            'message' => 'Pembayaran berhasil, menunggu persetujuan admin',
            'payment' => $payment
        ]);
    }

}