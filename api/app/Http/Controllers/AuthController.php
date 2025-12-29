<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    //hanles login functionality
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            /** @var User|null $user */
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'isSuccess' => false,
                    'message' => 'Invalid Credentials',
                ], 401);
            }

            // Revoke old tokens (optional but recommended)
            $user->tokens()->delete();

            // Create Sanctum token
            $token = $user->createToken('api-token')->plainTextToken;

            $userData = $user->toArray();
            $userData['created_at'] = $user->created_at->format('Y-m-d');

            return response()->json([
                'isSuccess' => true,
                'user' => $userData,
                'token' => $token,
                'token_type' => 'Bearer',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }


    //handles register functionality
    public function register(Request $request)
    {
        try{
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:4',
            ]);

            $hashedPassword = Hash::make($request->password);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $hashedPassword,
            ]);

            $token = $user->createToken('api-token')->plainTextToken;
            $user = $user->toArray();
            $user['created_at'] = explode('T', $user['created_at'])[0];
            
            return response()->json([
                'isSuccess' => true,
                'user' => $user,
                'token' => $token,
            ], 201);

        }catch (ValidationException $e) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    //handles logout functionality
    public function logout(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'isSuccess' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $user->currentAccessToken()->delete();

        return response()->json([
            'isSuccess' => true,
            'message' => 'Logged out successfully',
        ]);
    }


}
