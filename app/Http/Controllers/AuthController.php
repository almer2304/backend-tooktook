<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique',
            'password' => 'required|min:5|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Berhasil membuat akun',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        $checkPassword = Hash::check($request->password, $user->password);

        if(!$user || !$checkPassword){
            return response()->json([
                'message' => 'The provided credentials are incorrect'
            ], 404);
        }

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Anda berhasil login',
            'token' => $token
        ]);
    }
}
