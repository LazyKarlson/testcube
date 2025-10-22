<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Check for case-insensitive email uniqueness
        $existingUser = User::whereRaw('LOWER(email) = ?', [strtolower($validated['email'])])->first();
        if ($existingUser) {
            return response()->json([
                'message' => 'The email has already been taken.',
                'errors' => [
                    'email' => ['The email has already been taken.'],
                ],
            ], 422);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        // Assign default author role
        $user->assignRole('author');

        // Fire the Registered event to trigger email verification
        event(new Registered($user));

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully. Please check your email to verify your account.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'roles' => $user->roles->pluck('name'),
                'created_at' => $user->created_at,
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Login user and create token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Find user by email (case-sensitive for now to debug)
        $user = User::where('email', $validated['email'])->first();

        // If not found, try case-insensitive
        if (! $user) {
            $user = User::whereRaw('LOWER(email) = LOWER(?)', [$validated['email']])->first();
        }

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create new token (keeping previous tokens for multiple sessions)
        // If you want single session only, uncomment: $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Logout user (revoke token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke all tokens for the user
        $user = $request->user();
        $user->tokens()->delete();

        // Clear any cached authentication
        auth()->guard('sanctum')->forgetUser();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        $user = $request->user();
        $user->load('roles.permissions');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->roles->flatMap->permissions->pluck('name')->unique()->values(),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    /**
     * Send email verification notification
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 400);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification email sent',
        ]);
    }

    /**
     * Verify email address
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        $user = User::findOrFail($request->route('id'));

        if (! hash_equals(
            (string) $request->route('hash'),
            sha1($user->getEmailForVerification())
        )) {
            return response()->json([
                'message' => 'Invalid verification link',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 400);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }

    /**
     * Check if email is verified
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkEmailVerification(Request $request)
    {
        return response()->json([
            'verified' => $request->user()->hasVerifiedEmail(),
            'email' => $request->user()->email,
        ]);
    }
}
