<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Store;
use Illuminate\Http\Request;

class CameraController extends Controller
{
    public function index()
    {
        $cameras = Camera::all();

        return response()->json([
            'message' => 'Data camera berhasil diambil',
            'cameras' => $cameras
        ]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'stock' => 'required|integer',
            'price_per_day' => 'required|integer'
        ]);

        $store = Store::find($request->store_id);

        if($store->user_id !== auth()->id()){
            return response()->json([
                'message' => 'Unauthorized'
            ],403);
        }

        $camera = Camera::create([
            'store_id' => $store,
            'name' => $request->name,
            'description' => $request->description,
            'stock' => $request->stock,
            'price_per_day' => $request->price_per_day
        ]);

        return response()->json([
            'message' => 'Berhasil membuat camera',
            'camera' => $camera
        ]);
    }
}
