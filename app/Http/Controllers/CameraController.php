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

    public function count()
    {
        $cameras = Camera::all();

        $countCamera = count($cameras);

        return response()->json(["Jumlah kamera" => $countCamera]);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'stock' => 'required|integer',
            'price_per_day' => 'required|integer'
        ]);

        $camera = Camera::create([
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

    public function update(Request $request, Camera $camera)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string',
            'stock' => 'sometimes|integer',
            'price_per_day' => 'sometimes|integer'
        ]);

        $camera->update($validated);

        return response()->json([$camera]);
    }

    public function delete(Camera $camera)
    {
        $camera->delete();

        return response()->json([
            'message' => 'Berhasil menghapus kamera'
        ]);
    }
}
