<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LoginLog;
use Illuminate\Support\Facades\DB;

class AuthenticatedSessionController extends Controller
{
    // ─── Login ────────────────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Get user
        $user = User::where('email', $request->email)->first();

        // Unknown email
        if (!$user) {
            $this->writeLoginLog($request, null, $request->email, 'failed', 'Email not found');
            return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
        }

        // Wrong password
        if (!Hash::check($request->password, $user->password_hash)) {
            $this->writeLoginLog($request, $user->id, $request->email, 'failed', 'Incorrect password');
            return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
        }

        // Inactive account
        if ($user->status !== 'active') {
            $this->writeLoginLog($request, $user->id, $request->email, 'failed', 'Account inactive');
            return response()->json(['message' => 'Your account is inactive. Please contact the administrator.'], 403);
        }

        // Check if password change is required
        if ($user->must_change_password) {
            return response()->json([
                'message' => 'Password must be changed before login',
                'user' => $user->only(['id', 'email', 'full_name', 'must_change_password'])
            ], 200);
        }

        // Delete old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('api-token', ['*'], now()->addDays(7))->plainTextToken;

        // Update last login
        $user->last_login = now();
        $user->save();

        // Write log
        $this->writeLoginLog($request, $user->id, $request->email, 'success', 'Login successful');

        // Load role relationship
        $user->load('role:id,name');

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role ? ['id' => $user->role->id, 'name' => $user->role->name] : null,
            ],
        ]);
    }

    /**
     * Write login log directly (no queue)
     */
    private function writeLoginLog(Request $request, $userId, $email, $status, $details)
    {
        try {
            LoginLog::create([
                'user_id'         => $userId,
                'action'          => 'login',
                'email_attempted' => $email,
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
                'status'          => $status,
                'details'         => $details,
            ]);
        } catch (\Exception $e) {
            // Log error but don't crash the application
            \Log::error('Failed to write login log: ' . $e->getMessage());
        }
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // Delete current token
            $user->currentAccessToken()->delete();

            // Write log
            $this->writeLoginLog($request, $user->id, $user->email, 'success', 'Logout successful');
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    // ─── Change Password ──────────────────────────────────────────────────────

    public function change(Request $request)
    {
        $request->validate([
            'email'            => 'required|email',
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!Hash::check($request->current_password, $user->password_hash)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        // Update password
        $user->password_hash = Hash::make($request->new_password);
        $user->must_change_password = false;
        $user->save();

        // Delete all tokens for security
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password updated successfully. Please login with your new password.',
        ]);
    }
}   