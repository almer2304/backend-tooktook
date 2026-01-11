<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use Illuminate\Container\Attributes\Auth;

class StoreController extends Controller
{
    public function index()
    {
        $store = Store::all();

        return response()->json([$store]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        $store = Store::create([
            'user_id' => auth()->id(),
            'store_name' => $request->store_name,
            'description' => $request->description,
            'address' => $request->address
        ]);

        return response()->json([
            'message' => 'Kamu berhasil membuat store baru',
            'store' => $store
        ]);
    }

    public function update(Request $request, Store $store)
    {
        $validated = $request->validate([
            'store_name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        if($store->user_id !== auth()->id()){
            return response()->json([
                'message' => 'Unauthorize'
            ], 403);
        }

        $store->update($validated);

        return response()->json([
            'message' => 'Berhasil data store',
            'store' => $store
        ]);
    }

    public function delete(Store $store)
    {
        if($store->user_id !== auth()->id()){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
        }

        $store->delete();

        return response()->json([
            'message' => 'Berhasil menghapus store'
        ]);
    }
}
