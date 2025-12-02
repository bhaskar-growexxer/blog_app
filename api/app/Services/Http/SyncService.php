<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class SyncService
{
    protected $baseUrl;
    protected $username;
    protected $password;
    protected $tokenKey = 'sync_api_token';

    public function __construct()
    {
        $this->baseUrl  = env('SYNC_API');
        $this->username = env('SYNC_API_USERNAME');
        $this->password = env('SYNC_API_PASSWORD');
    }

    /**
     * Get Auth Token (from Redis or external API)
     */
    public function getToken()
    {
        // 1) Try Redis first
        $token = Redis::get($this->tokenKey);

        if ($token) {
            return $token;
        }

        // 2) Fetch new token from API
        $response = Http::post($this->baseUrl . '/auth/login', [
            'username' => $this->username,
            'password' => $this->password,
        ]);

        if ($response->failed()) {
            return null;
        }

        $token = $response->json()['token'] ?? null;

        if ($token) {
            // Save token to Redis with TTL
            $ttl = env('SYNC_TOKEN_TTL', 3600);
            Redis::setex($this->tokenKey, $ttl, $token);
        }

        return $token;
    }

    /**
     * Sync Blog Data to External API
     */
    public function syncBlog(array $blog)
    {
        $token = $this->getToken();

        if (!$token) {
            return ['error' => 'Token not available'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->post($this->baseUrl . '/blog/sync', $blog);

        // If token expired → refresh and retry
        if ($response->status() == 401) {
            Redis::del($this->tokenKey);   // Delete expired token
            $token = $this->getToken();    // Get new one

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->post($this->baseUrl . '/blog/sync', $blog);
        }

        return $response->json();
    }

    /**
     * Example: GET data from external API
     */
    public function getData($endpoint)
    {
        $token = $this->getToken();

        if (!$token) {
            return ['error' => 'Token not available'];
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->get($this->baseUrl . '/' . $endpoint)
          ->json();
    }
}
