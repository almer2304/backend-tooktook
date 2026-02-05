<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Container\Attributes\Auth;

class UserController extends Controller
{
    public function forAdmin()
    {
        $user = User::all();

        return response()->json([
            'message' => 'Data profile semua user',
            'data' => $user
        ]);
    }

    public function forUser()
    {
        $user = auth()->user()->only(['name', 'email']);

        return response()->json([
            'message' => 'Data profile user',
            'data' => $user
        ]);
    }
}
