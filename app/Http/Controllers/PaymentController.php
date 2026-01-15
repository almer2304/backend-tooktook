<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * GET /payments
     * User: lihat payment miliknya
     * Store/Admin: lihat semua
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->role === 'user') {
            $payments = Payment::whereHas('rental', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with('rental')->get();
        } else {
            $payments = Payment::with('rental')->get();
        }

        return response()->json($payments);
    }

    /**
     * POST /payments
     * User membuat payment untuk rental miliknya
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rental_id' => 'required|exists:rentals,id',
            'method' => 'required|string',
        ]);

        $rental = Rental::where('id', $validated['rental_id'])
            ->where('user_id', auth()->id())
            ->first();

        if (!$rental) {
            return response()->json([
                'message' => 'Rental tidak ditemukan atau bukan milik anda'
            ], 403);
        }

        if ($rental->payment) {
            return response()->json([
                'message' => 'Payment untuk rental ini sudah ada'
            ], 400);
        }

        $payment = Payment::create([
            'rental_id' => $rental->id,
            'amount' => $rental->total_price ?? 0,
            'method' => $validated['method'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Payment berhasil dibuat',
            'payment' => $payment
        ], 201);
    }

    /**
     * GET /payments/{payment}
     */
    public function show(Payment $payment)
    {
        $user = auth()->user();

        if (
            $user->role === 'user' &&
            $payment->rental->user_id !== $user->id
        ) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json($payment->load('rental'));
    }

    /**
     * PUT /payments/{payment}/pay
     * Simulasi pembayaran sukses
     */
    public function pay(Payment $payment)
    {
        if ($payment->status === 'paid') {
            return response()->json([
                'message' => 'Payment sudah dibayar'
            ], 400);
        }

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payment berhasil dibayar',
            'payment' => $payment
        ]);
    }
}
