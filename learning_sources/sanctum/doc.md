# Laravel Sanctum Authentication – Developer Documentation

This document explains how to implement **API authentication using Laravel Sanctum** in an existing Laravel application. It is written for backend developers and assumes basic Laravel knowledge.

---

## 1. What is Laravel Sanctum?

Laravel Sanctum provides a lightweight authentication system for:

* SPAs (Angular, React, Vue)
* Mobile applications
* Token-based API authentication

It supports:

* Cookie-based auth for SPAs
* Personal access tokens for APIs

---

## 2. Use Case

* Frontend: Angular / React
* Backend: Laravel API
* Auth Type: Token-based (Bearer Token)

---

## 3. Installation

### 3.1 Install Sanctum

```bash
composer require laravel/sanctum
```

### 3.2 Publish Config & Migration

```bash
php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"
```

### 3.3 Run Migrations

```bash
php artisan migrate
```

This creates the `personal_access_tokens` table.

---

## 4. Configure Sanctum

### 4.1 Enable Sanctum Middleware

Edit `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

---

## 5. User Model Configuration

Edit `app/Models/User.php`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
}
```

---

## 6. Auth Routes

Edit `routes/api.php`:

```php
use App\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});
```

---

## 7. Auth Controller

Create controller:

```bash
php artisan make:controller AuthController
```

### 7.1 Controller Implementation

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register new user
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
```

---

## 8. Protecting Routes

Use middleware:

```php
Route::middleware('auth:sanctum')->get('/orders', function () {
    return ['orders' => []];
});
```

---

## 9. API Request Headers

Frontend must send token:

```
Authorization: Bearer <token>
Accept: application/json
```

---

## 10. Token Management

### Revoke All Tokens

```php
$user->tokens()->delete();
```

### Create Token with Abilities

```php
$user->createToken('mobile', ['read', 'write']);
```

---

## 11. Common Errors

| Issue            | Solution             |
| ---------------- | -------------------- |
| 401 Unauthorized | Check Bearer token   |
| Token not saved  | Run migrations       |
| CORS issue       | Configure `cors.php` |

---

## 12. Best Practices

* Use HTTPS in production
* Rotate tokens on sensitive actions
* Limit token abilities
* Expire tokens using scheduler if needed

---

## 13. Summary

Laravel Sanctum is ideal for lightweight API authentication. It is easy to integrate, secure, and SPA-friendly.

---

**Author:** Bhasker
**Framework:** Laravel 10 / 11
**Auth Method:** Sanctum Token Authentication
