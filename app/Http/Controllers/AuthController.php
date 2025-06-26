<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User; // Adjust namespace as needed


class AuthController extends Controller
{
    //
    public function login(Request $request)
    {
        // Function level rate limit
        $key = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        // change this bro?
        $decayMinutes = 15;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            Log::warning('Rate limit exceeded for login', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response([
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after' => $seconds
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|max:255',
            'password' => 'required|min:8|max:255',
        ]);

        if ($validator->fails()) {
            // Failed log for validation
            Log::info('Login validation failed', [
                'ip' => $request->ip(),
                'errors' => $validator->errors()->toArray()
            ]);

            return response([
                'message' => 'Invalid input provided.',
                'errors' => $validator->errors()->toArray()
            ], 422);
        }

        $email = $request->input('email');
        $password = $request->input('password');

        // Active check
        $user = User::where('email', $email)->first();

        if (!$user || !$user->isActive()) {
            // This will increment the rate limit
            RateLimiter::hit($key, $decayMinutes * 60);

            // Failed log for failed attemp
            Log::warning('Failed login attempt', [
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'reason' => $user ? 'inactive_account' : 'user_not_found'
            ]);

            // Prevent enum
            sleep(1);
            return response([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // Block locked out
        if ($user->isLockedOut()) {
            Log::warning('Login attempt on locked account', [
                'user_id' => $user->id,
                'email' => $email,
                'ip' => $request->ip(),
            ]);

            return response([
                'message' => 'Account is temporarily locked. Please try again later.',
            ], 423);
        }

        // Attempt authentication
        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            // Increment failed attempts for this user
            $user->increment('failed_login_attempts');
            $user->last_failed_login = now();

            // Lock account
            if ($user->failed_login_attempts >= 5) {
                $user->locked_until = now()->addMinutes(30);
                Log::alert('Account locked due to multiple failed login attempts', [
                    'user_id' => $user->id,
                    'email' => $email,
                    'ip' => $request->ip(),
                ]);
            }

            $user->save();

            RateLimiter::hit($key, $decayMinutes * 60);
            Log::warning('Failed login authentication', [
                'user_id' => $user->id,
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'failed_attempts' => $user->failed_login_attempts
            ]);

            // Consistent timing
            sleep(1);
            return response([
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        // Reset failed attempts
        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->last_login = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        RateLimiter::clear($key);
        $token = $user->createToken('api_token', ['*'], now()->addHours(24))->plainTextToken;

        // Success
        Log::info('Successful login', [
            'user_id' => $user->id,
            'email' => $email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response([
            'message'    => 'Login successful!',
            'token'      => $token,
            'user'       => $user,
            'token_type' => 'Bearer',
            'expires_in' => 86400,
        ], 200);
    }


    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response([
                    'message' => 'Not authenticated.',
                ], 401);
            }

            $request->user()->currentAccessToken()->delete();

            // Logout log
            Log::info('User logout from current device', [
                'user_id' => $user->id,
                'email' => $user->email,
                'tokens_revoked' => 1,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response([
                'message' => 'Logged out successfully!',
                'tokens_revoked' => 1,
            ], 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'An error occurred during logout.',
            ], 500);
        }
    }


    public function user(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isActive()) {
                Log::warning('Inactive user attempted to access profile', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip(),
                ]);

                return response([
                    'message' => 'Account is inactive',
                ], 403);
            }

            return response([
                'user' => $user,
            ], 200);
        } catch (\Exception $e) {

            return response([
                'message' => 'An error occurred',
            ], 500);
        }
    }
}
