<?php
require 'vendor/autoload.php';

// Replace with your actual connection string
$connectionString = 'mongodb+srv://boreddyb2018_db_user:IDRDNfe1jKuOnwmF@ac-magqfx0-shard-00-00.kpyq73p.mongodb.net/laravel_blog?ssl=true&replicaSet=atlas-xyz-shard-0&authSource=admin&retryWrites=true&w=majority';

try {
    $client = new MongoDB\Client($connectionString, [
        'serverSelectionTimeoutMS' => 10000,
    ], [
        'allow_invalid_hostname' => false,
        'ca_file' => '/etc/ssl/certs/ca-certificates.crt',

    ]);
    
    echo "Testing connection...\n";
    $databases = $client->listDatabases();
    echo "✓ Connected successfully!\n";
    print_r($databases);
    
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}