<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LoginLog;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        $status = 'failed';

        if ($user && Hash::check($request->password, $user->password_hash) && $user->status === 'active') {
            $status = 'success';
            
            // Delete existing tokens
            $user->tokens()->delete();
            
            $token = $user->createToken('api-token')->plainTextToken;

            $user->last_login = now();
            $user->save();
        }

        LoginLog::create([
            'user_id' => $user?->id,
            'email_attempted' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status
        ]);

        if ($status === 'success') {
            return response()->json([
                'message' => 'Login successful',
                'token' => $token,
                'user' => $user->load('role')
            ]);
        }

        return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
    }

    /**
     * Destroy an authenticated session.
     */
    public function logout(Request $request)
    {
 
        $bearerToken = $request->bearerToken();
        if ($bearerToken) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
            if ($token) {
                $token->delete();
                return response()->json(['message' => 'Logged out successfully']);
            }
        }
        
        return response()->json(['message' => 'Logged out (no active session)']);
    }
}