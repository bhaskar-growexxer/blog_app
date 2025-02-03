<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

define('AUTH_SANCTUM', 'auth:sanctum');

Route::group(['prefix' => 'auth', 'controller' => AuthController::class], function () {
    Route::post('/login', ['as' => 'login', 'uses' => 'login']);
    Route::post('/register', 'register');
    Route::post('/logout', ['middleware' => AUTH_SANCTUM,'as' => 'logout', 'uses' => 'logout']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => AUTH_SANCTUM, 'prefix' => 'blogs', 'controller' => BlogController::class], function () {
    Route::get('/', 'index');
    Route::post('/', 'store');

    $blogId = '/{id}';
    Route::get($blogId, 'show');
    Route::put($blogId, 'update');
    Route::delete($blogId, 'destroy');
});
