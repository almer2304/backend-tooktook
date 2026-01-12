<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::all();
        
        return response()->json([$items]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:80',
            'description' => 'nullable|string',
            'stock' => 'required',
            'price_per_day' => 'required'
        ]);

        $items = Item::create([
            'user_id' => Auth::id(),
            
        ])
    }
}
