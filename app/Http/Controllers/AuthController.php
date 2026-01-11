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
            'email' => 'required|email',
            'password' => 'required|min:5|confirmed',
            'role' => 'required|in:user,store,admin'  
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
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

        if($user->role === 'user'){
            return response()->json([
                'message' => 'Anda berhasil login sebagai user',
                'token' => $token,
                'role' => $user->role
            ]);
        }
        if($user->role === 'store'){
            return response()->json([
                'message' => 'Anda berhasil login sebagai store',
                'token' => $token,
                'role' =>  $user->role
            ]);
        }
        if($user->role === 'admin'){
            return response()->json([
                'message' => 'Anda berhasil login sebagai admin',
                'token' => $token,
                'role' => $user->role
            ]);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Anda berhasil logout'
        ]);
    }
}
