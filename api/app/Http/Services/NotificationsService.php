<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class NotificationService
{
    /**
     * Fetch notifications for the logged-in user with optional filters.
     *
     * @param array $filters ['status' => 'read'|'unread'|'all', 'since' => Carbon|string|null]
     * @return \Illuminate\Support\Collection
     */
    public function getUserNotifications(array $filters = [])
    {
        $user = Auth::user();

        if (!$user) {
            return collect([]);
        }

        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by read/unread status
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'unread') {
                $query->where('is_read', false);
            } elseif ($filters['status'] === 'read') {
                $query->where('is_read', true);
            }
        }

        // Filter by date (e.g., last 24 hours)
        if (!empty($filters['since'])) {
            $since = $filters['since'] instanceof Carbon
                ? $filters['since']
                : Carbon::parse($filters['since']);
            $query->where('created_at', '>=', $since);
        }

        return $query->take(10)
            ->get(['id', 'title', 'message', 'is_read', 'created_at']);
    }

    /**
     * Insert a new notification for a specific user.
     *
     * @param int $userId
     * @param string $title
     * @param string $message
     * @return Notification
     */
    public function addNotification(int $userId, string $title, string $message)
    {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
