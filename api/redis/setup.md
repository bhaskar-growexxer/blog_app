---

# 📄 Redis Caching Setup for Laravel

## 1. **Install Redis Server**

### Linux / macOS:

```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable redis
sudo systemctl start redis
```

Check status:

```bash
redis-cli ping
# Should return PONG
```

### Windows:

* Use **Memurai** or **Redis Windows build**
* Ensure Redis service is running

---

## 2. **Install PHP Redis Extension**

### Linux / macOS:

```bash
sudo pecl install redis
```

Add to `php.ini`:

```
extension=redis
```

### Windows (XAMPP/WAMP):

* Download `php_redis.dll` matching your PHP version
* Add to `ext/` folder
* Add to `php.ini`:

```
extension=php_redis.dll
```

Check installation:

```bash
php -m | grep redis
```

---

## 3. **Update `.env`**

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

REDIS_DB=0
REDIS_CACHE_DB=1
```

> `REDIS_DB` → default Redis DB for application
> `REDIS_CACHE_DB` → optional separate DB for cache if using `Cache` facade

---

## 4. **Check `config/database.php` Redis Section**

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],

    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

---

## 5. **Controller Setup**

### Example: BlogController with Redis caching

```php
use Illuminate\Support\Facades\Redis;
use DateTime;
use DateTimeZone;

class BlogController extends Controller
{
    const TIMEZONE = 'Asia/Kolkata';

    private function getCachedBlogs(Request $request): array
    {
        $cacheKey = 'blogs_' . md5(json_encode([
            'category' => $request->category,
            'search'   => $request->search,
        ]));

        $cached = Redis::get($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        if ($request->category) {
            $blogs = Blog::where('category', $request->category)->get();
        } elseif ($request->search) {
            $blogs = Blog::where('title', 'like', '%' . $request->search . '%')
                        ->orWhere('description', 'like', '%' . $request->search . '%')
                        ->orWhere('author', 'like', '%' . $request->search . '%')
                        ->get();
        } else {
            $blogs = Blog::all();
        }

        $blogsArray = array_map(function ($blog) {
            $dateTime = new DateTime($blog['created_at']);
            $blog['created_at'] = $dateTime
                ->setTimezone(new DateTimeZone(self::TIMEZONE))
                ->format('H:i d M Y');
            return $blog;
        }, $blogs->toArray());

        Redis::setex($cacheKey, 600, json_encode($blogsArray));

        return $blogsArray;
    }

    public function index(Request $request)
    {
        $blogs = $this->getCachedBlogs($request);

        return response()->json([
            'isSuccess' => true,
            'data' => $blogs
        ], 200);
    }
}
```

> `setex(key, ttl, value)` stores the cache in **Redis DB 0** for 600 seconds.

---

## 6. **Clear Cache (Optional)**

To delete a single key:

```php
Redis::del('blogs_somehashkey');
```

Or flush entire Redis DB (development only!):

```bash
redis-cli FLUSHDB
```

---

## 7. **Test Cache**

```bash
php artisan tinker
>>> Redis::setex('test', 60, 'hello')
>>> Redis::get('test')
"hello"
```

---

