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

    if (!$user) {
        return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
    }

    // Check password first
    if (!Hash::check($request->password, $user->password_hash)) {
        return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
    }

    // Now password is correct
    if ($user->must_change_password) {
        return response()->json([
            'message' => 'Password must be changed before login',
            'user' => $user
        ], 200); // Vue modal will open
    }

    // Password correct & no need to change → normal login
    if ($user->status === 'active') {
        $status = 'success';

        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;

        $user->last_login = now();
        $user->save();

        LoginLog::create([
            'user_id' => $user->id,
            'email_attempted' => $request->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => $status
        ]);

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

    /**
     * Change the user password.
     */
public function change(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'current_password' => 'required|string',
        'new_password' => 'required|string|min:6|confirmed',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    if (!Hash::check($request->current_password, $user->password_hash)) {
        return response()->json(['message' => 'Current password is incorrect'], 422);
    }

    $user->password_hash = Hash::make($request->new_password);
    $user->must_change_password = false;
    $user->update();

    // Don't create a token here - user must login manually
    // Just return success message

    return response()->json([
        'message' => 'Password updated successfully. Please login with your new password.'
    ]);
}
}