<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                
                // Check if user is active
                if (!$user->is_active) {
                    Auth::logout();
                    return response()->json([
                        'isSuccess' => false,
                        'message' => 'Your account is inactive'
                    ], 403);
                }

                $token = $user->createToken('api-token')->plainTextToken;

                return response()->json([
                    'isSuccess' => true,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->formatted_created_at,
                    ],
                    'token' => $token
                ]);
            }

            return response()->json([
                'isSuccess' => false,
                'message' => 'Invalid Credentials'
            ], 401);

        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            $token = $user->createToken('api-token')->plainTextToken;
            
            return response()->json([
                'isSuccess' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->formatted_created_at,
                ],
                'token' => $token,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'isSuccess' => true,
                'message' => 'Logged out successfully'
            ]);
        }

        return response()->json([
            'isSuccess' => false,
            'message' => 'User not authenticated'
        ], 401);
    }
}