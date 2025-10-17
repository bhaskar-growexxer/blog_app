<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;

Route::get('/notifications/stream', function (NotificationService $notificationService) {
    if (!Auth::check()) {
        abort(403, 'Unauthorized');
    }

    $response = new StreamedResponse(function () use ($notificationService) {
        while (true) {
            $notifications = $notificationService->getUserNotifications();

            echo "data: " . json_encode($notifications) . "\n\n";
            ob_flush();
            flush();

            if (connection_aborted()) {
                break;
            }

            sleep(5);
        }
    });

    $response->headers->set('Content-Type', 'text/event-stream');
    $response->headers->set('Cache-Control', 'no-cache');
    $response->headers->set('Connection', 'keep-alive');

    return $response;
})->middleware('auth:sanctum');
