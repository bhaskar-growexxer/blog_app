# 📘 Laravel HTTP Client – Index

This document explains the basics of API integration in Laravel using:

* `Illuminate\Support\Facades\Http`
* How it compares to GuzzleHTTP
* Available useful functions
* Overview of our SyncService implementation

---

# 1. What is Laravel HTTP Client?

Laravel provides a clean, modern wrapper around GuzzleHTTP for making HTTP requests. It simplifies:

* GET & POST requests
* Authentication
* Headers
* JSON requests
* Error handling
* Timeouts & retries

Import the facade:

```php
use Illuminate\Support\Facades\Http;
```

---

# 2. GuzzleHTTP vs Laravel HTTP Facade

| Feature          | GuzzleHTTP                      | Laravel `Http::` Facade                     |
| ---------------- | ------------------------------- | ------------------------------------------- |
| Usage            | Requires direct client creation | Simple `Http::get()` style                  |
| Boilerplate      | More verbose                    | Very minimal                                |
| Built-in Retries | Manual code                     | `retry()` available                         |
| JSON Handling    | Requires decoding               | Auto JSON: `$response->json()`              |
| Headers & Auth   | Manual                          | Simple chainable methods                    |
| Error Handling   | Manual checks                   | `failed()`, `successful()`, `serverError()` |
| Testing          | Harder                          | Built-in `Http::fake()`                     |
| Package          | Direct Guzzle package           | Uses Guzzle internally                      |

### Summary

Laravel's `Http` facade **makes 90% of API work easier** while still using Guzzle internally. You get power + simplicity.

---

# 3. Commonly Used Laravel HTTP Methods

## 🔹 GET Request

```php
Http::get(url);
```

## 🔹 POST Request with body

```php
Http::post(url, [ 'key' => 'value' ]);
```

## 🔹 With Custom Headers

```php
Http::withHeaders([...])->get(url);
```

## 🔹 With Token

```php
Http::withToken($token)->get(url);
```

## 🔹 Retry Failed Requests

```php
Http::retry(3, 200)->get(url);
```

## 🔹 Timeout

```php
Http::timeout(5)->get(url);
```

## 🔹 Handle Errors

```php
$response->successful();
$response->failed();
$response->clientError();
$response->serverError();
```

## 🔹 Send File

```php
Http::attach('file', file_get_contents($path), 'image.jpg')->post(url);
```

---

# 4. Our Implementation Overview (SyncService)

We created a **SyncService** to:

* Authenticate with external API using username & password
* Store auth token in Redis
* Automatically refresh expired tokens
* Send blog data to external system
* Provide reusable `getData()` method for GET endpoints

## What SyncService Does

### ✔ 1. Reads API credentials from `.env`

```
SYNC_API
SYNC_API_USERNAME
SYNC_API_PASSWORD
SYNC_TOKEN_TTL
```

### ✔ 2. Fetches & stores token in Redis

Token is saved using:

```
Redis::setex('sync_api_token', ttl, token)
```

### ✔ 3. Provides `syncBlog()` method

Used after each blog creation.

### ✔ 4. Provides `getData()` method

For any GET request to SYNC API.

### ✔ 5. Auto-refreshes expired token

On `401`, token is deleted and request retried.

---

# 5. Why This Architecture is Good

✔ Reduces API load (token stored in Redis)
✔ Clean, reusable service class
✔ Avoids repeating authentication logic
✔ Works with any external API
✔ Follows Laravel standards
✔ Easy error handling & debugging
✔ Supports caching & retries

---

# 6. Summary

Laravel's `Http` Facade provides a powerful and elegant way to work with external APIs. Compared to raw Guzzle, it is simpler, cleaner, and better integrated into the Laravel ecosystem.

Our SyncService is a real-world example of:

* Authenticating external API
* Storing tokens in Redis
* Handling expiration
* Syncing blog data
* Making GET & POST calls cleanly through Laravel's HTTP client.
