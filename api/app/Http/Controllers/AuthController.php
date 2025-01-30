<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    //create function for login , we are using sanctum middleware
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt($request->only('email', 'password'))) {
            return response([
                'error' => ['Invalid credentials.']
            ], 404);
        }

        $user = auth()->user();

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

}

// //authenticate
// <?php

// namespace App\Http\Middleware;

// use Illuminate\Auth\Middleware\Authenticate as Middleware;
// use Illuminate\Http\Request;

// class Authenticate extends Middleware
// {
//     /**
//      * Get the path the user should be redirected to when they are not authenticated.
//      */
//     protected function redirectTo(Request $request): ?string
//     {
//         return $request->expectsJson() ? null : route('api/auth/login');
//     }
// }


// //api.php

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// //user.php
// class User extends Authenticatable
// {
//     use HasApiTokens, HasFactory, Notifiable;

//     /**
//      * The attributes that are mass assignable.
//      *
//      * @var array<int, string>
//      */
//     protected $fillable = [
//         'name',
//         'email',
//         'password',
//     ];
//}


// //  create a login function in the controller and also fill code in the middleware file.