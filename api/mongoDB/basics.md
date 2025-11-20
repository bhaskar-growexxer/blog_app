---

# **MongoDB Basics (Laravel Guide)**

This document explains how to use MongoDB inside a Laravel application using the **Jenssegers MongoDB** library.

---

## **1. Configure MongoDB Connection (config/database.php)**

Add the MongoDB connection inside the `connections` array:

```php
'mongodb' => [
    'driver'   => 'mongodb',
    'host'     => env('MONGO_HOST', '127.0.0.1'),
    'port'     => env('MONGO_PORT', 27017),
    'database' => env('MONGO_DATABASE', ''),
    'username' => env('MONGO_USERNAME', ''),
    'password' => env('MONGO_PASSWORD', ''),
    'options'  => [
        'database' => env('MONGO_AUTH_DB', 'admin'),
    ]
],
```

---

## **2. Environment Variables (.env)**

```env
MONGO_HOST=127.0.0.1
MONGO_PORT=27017
MONGO_DATABASE=blog_app
MONGO_USERNAME=
MONGO_PASSWORD=
MONGO_AUTH_DB=admin
```

---

## **3. MongoDB Model Example**

```php
<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model;

class BlogMongo extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'blogs';

    protected $fillable = [
        'title',
        'description',
        'category',
        'author',
        'author_email',
        'created_at',
    ];
}
```

---

## **4. Insert Document (Equivalent to insertOne)**

```php
BlogMongo::create([
    'title' => 'Welcome',
    'category' => 'General',
    'description' => 'Intro blog',
    'author' => 'Bhaskar',
    'author_email' => 'test@example.com',
]);
```

---

## **5. Find Documents**

### **Find All**

```php
$blogs = BlogMongo::all();
```

### **Find by ID**

```php
$blog = BlogMongo::find($id);
```

### **Filter**

```php
$blogs = BlogMongo::where('category', 'Tech')->get();
```

### **Search**

```php
$blogs = BlogMongo::where('title', 'like', '%php%')
    ->orWhere('description', 'like', '%php%')
    ->orWhere('author', 'like', '%php%')
    ->get();
```

---

## **6. Update Document (Equivalent to updateOne)**

### **Eloquent Style**

```php
$blog = BlogMongo::find($id);

$blog->update([
    'title' => 'Updated Title',
    'category' => 'News',
]);
```

### **Raw Mongo updateOne**

```php
BlogMongo::raw(function($collection) use ($id) {
    return $collection->updateOne(
        ['_id' => new MongoDB\BSON\ObjectId($id)],
        ['$set' => ['title' => 'Updated']]
    );
});
```

---

## **7. Delete Document**

### **Eloquent**

```php
BlogMongo::find($id)->delete();
```

### **Raw deleteOne**

```php
BlogMongo::raw(function($collection) use ($id) {
    return $collection->deleteOne([
        '_id' => new MongoDB\BSON\ObjectId($id)
    ]);
});
```

---

## **8. Aggregation Examples**

### **Group By Category**

```php
$results = BlogMongo::raw(function($collection){
    return $collection->aggregate([
        [
            '$group' => [
                '_id' => '$category',
                'count' => ['$sum' => 1],
            ]
        ]
    ]);
});
```

### **Match + Sort + Limit**

```php
$results = BlogMongo::raw(function($collection){
    return $collection->aggregate([
        ['$match' => ['category' => 'Tech']],
        ['$sort' => ['created_at' => -1]],
        ['$limit' => 10]
    ]);
});
```

---

## **9. Creating a Reusable Service Layer**

```php
<?php

namespace App\Services;

use App\Models\BlogMongo;

class BlogService
{
    /**
     * Insert Blog
     */
    public static function create(array $data)
    {
        return BlogMongo::create($data);
    }

    /**
     * Get Blogs by Filter
     */
    public static function get(array $filter = [])
    {
        return BlogMongo::where($filter)->get();
    }

    /**
     * Update Blog
     */
    public static function update(string $id, array $data)
    {
        return BlogMongo::where('_id', $id)->update($data);
    }

    /**
     * Delete Blog
     */
    public static function delete(string $id)
    {
        return BlogMongo::where('_id', $id)->delete();
    }
}
```

---

## **10. Controller Usage Example**

```php
public function store(Request $request)
{
    $blog = BlogService::create($request->all());

    return response()->json([
        'success' => true,
        'data' => $blog
    ], 200);
}
```

---
