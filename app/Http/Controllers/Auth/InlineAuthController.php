<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class InlineAuthController extends Controller
{
    /**
     * Handle an inline JSON login request.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $loginField = $request->email;
        $password = $request->password;
        $remember = (bool)$request->remember;

        // Support login by email, phone, or username
        $user = User::where('email', $loginField)
            ->orWhere('phone', $loginField)
            ->orWhere('username', $loginField)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'errors' => [
                    'email' => ['These credentials do not match our records.']
                ]
            ], 422);
        }

        if (!$user->is_active) {
            return response()->json([
                'errors' => [
                    'email' => ['Your account is deactivated. Please contact support.']
                ]
            ], 403);
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'area' => $user->area,
                'city' => $user->city,
            ],
            'message' => 'Logged in successfully'
        ]);
    }

    /**
     * Handle an inline JSON registration request.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'digits:11', 'unique:users,phone'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $username = $request->username;
        if (!$username) {
            $base = explode('@', $request->email)[0];
            $username = preg_replace('/[^a-zA-Z0-9]/', '', $base) . rand(100, 999);
            while (User::where('username', $username)->exists()) {
                $username = preg_replace('/[^a-zA-Z0-9]/', '', $base) . rand(100, 9999);
            }
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $username,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        try {
            \Spatie\Permission\Models\Role::findOrCreate('customer');
            $user->assignRole('customer');
        } catch (\Throwable $e) {
            // Ignore if role already assigned
        }

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'area' => $user->area,
                'city' => $user->city,
            ],
            'message' => 'Account created and authenticated successfully'
        ]);
    }
}
