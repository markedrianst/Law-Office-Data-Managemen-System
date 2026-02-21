<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\LoginLog;

class AuthenticatedSessionController extends Controller
{
    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Write a login log entry.
     */
    private function writeLog(array $data): void
    {
        LoginLog::create($data);
    }

    /**
     * Build the base log payload shared across all actions.
     */
    private function baseLog(Request $request, string $action, ?int $userId, string $email, string $status): array
    {
        return [
            'user_id'         => $userId,
            'action'          => $action,
            'email_attempted' => $email,
            'ip_address'      => $request->ip(),
            'user_agent'      => $request->userAgent(),
            'status'          => $status,
        ];
    }

    // ─── Login ────────────────────────────────────────────────────────────────

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // Unknown email
        if (!$user) {
            $this->writeLog(array_merge(
                $this->baseLog($request, 'login', null, $request->email, 'failed'),
                ['details' => "LOGIN FAILED:\nReason: Email not found\nAttempted Email: {$request->email}"]
            ));

            return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
        }

        // Wrong password
        if (!Hash::check($request->password, $user->password_hash)) {
            $this->writeLog(array_merge(
                $this->baseLog($request, 'login', $user->id, $request->email, 'failed'),
                ['details' => "LOGIN FAILED:\nUser: {$user->full_name} ({$user->email})\nReason: Incorrect password"]
            ));

            return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
        }

        // Inactive account — surface a specific message to the frontend
        if ($user->status !== 'active') {
            $this->writeLog(array_merge(
                $this->baseLog($request, 'login', $user->id, $request->email, 'failed'),
                ['details' => "LOGIN FAILED:\nUser: {$user->full_name} ({$user->email})\nReason: Account is inactive"]
            ));

            return response()->json(['message' => 'Your account is inactive. Please contact the administrator.'], 403);
        }

        // Must change password — don't issue a token yet
        if ($user->must_change_password) {
            $this->writeLog(array_merge(
                $this->baseLog($request, 'login', $user->id, $request->email, 'failed'),
                ['details' => "LOGIN FAILED:\nUser: {$user->full_name} ({$user->email})\nReason: Password change required"]
            ));

            return response()->json([
                'message' => 'Password must be changed before login',
                'user'    => $user,
            ], 200);
        }

        // Success
        $user->tokens()->delete();
        $token = $user->createToken('api-token')->plainTextToken;
        $user->last_login = now();
        $user->save();

        $this->writeLog(array_merge(
            $this->baseLog($request, 'login', $user->id, $request->email, 'success'),
            ['details' => "LOGIN SUCCESSFUL:\nUser: {$user->full_name} ({$user->email})\nRole: " . ucfirst($user->role->name ?? 'Unknown') . "\nLast Login: " . ($user->getOriginal('last_login') ?? 'Never')]
        ));

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user->load('role'),
        ]);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No active session'], 401);
        }

        $request->user()->currentAccessToken()->delete();

        $this->writeLog(array_merge(
            $this->baseLog($request, 'logout', $user->id, $user->email, 'success'),
            ['details' => "LOGOUT SUCCESSFUL:\nUser: {$user->full_name} ({$user->email})\nRole: " . ucfirst($user->role->name ?? 'Unknown') . "\nLogout Time: " . now()->format('Y-m-d H:i:s')]
        ));

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

        // Unknown email
        if (!$user) {
            $this->writeLog(array_merge(
                $this->baseLog($request, 'password_change', null, $request->email, 'failed'),
                ['details' => "PASSWORD CHANGE FAILED:\nReason: User email not found\nAttempted Email: {$request->email}"]
            ));

            return response()->json(['message' => 'User not found'], 404);
        }

        // Wrong current password
        if (!Hash::check($request->current_password, $user->password_hash)) {
            $this->writeLog(array_merge(
                $this->baseLog($request, 'password_change', $user->id, $request->email, 'failed'),
                ['details' => "PASSWORD CHANGE FAILED:\nUser: {$user->full_name} ({$user->email})\nReason: Current password is incorrect\nTimestamp: " . now()->format('Y-m-d H:i:s')]
            ));

            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        // Update password
        $lastChanged = $user->updated_at?->format('Y-m-d H:i:s') ?? 'Never';

        $user->password_hash       = Hash::make($request->new_password);
        $user->must_change_password = false;
        $user->update();

        $this->writeLog(array_merge(
            $this->baseLog($request, 'password_change', $user->id, $request->email, 'success'),
            ['details' => "PASSWORD CHANGE SUCCESSFUL:\nUser: {$user->full_name} ({$user->email})\nRole: " . ucfirst($user->role->name ?? 'Unknown') . "\nPassword last changed: {$lastChanged}\nPassword change required flag: Reset to false\nChange Time: " . now()->format('Y-m-d H:i:s')]
        ));

        return response()->json([
            'message' => 'Password updated successfully. Please login with your new password.',
        ]);
    }
}