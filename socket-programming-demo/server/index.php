<?php
$host = "0.0.0.0";
$port = 9000;

$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_bind($server, $host, $port);
socket_listen($server);

$clients = [$server];

echo "Chat Server running on port $port ...\n";

while (true) {
    $read = $clients;
    socket_select($read, $write, $except, 0);

    foreach ($read as $sock) {

        // New Client Connected
        if ($sock === $server) {
            $client = socket_accept($server);
            $clients[] = $client;
            socket_write($client, "Welcome to PHP Chat!\n");
            continue;
        }

        // Read data from existing client
        $data = @socket_read($sock, 1024, PHP_NORMAL_READ);

        // Client disconnected
        if ($data === false) {
            $key = array_search($sock, $clients);
            unset($clients[$key]);
            socket_close($sock);
            continue;
        }

        $data = trim($data);
        if (!$data) continue;

        // Broadcast to everyone except server
        foreach ($clients as $client) {
            if ($client !== $server && $client !== $sock) {
                @socket_write($client, $data . "\n");
            }
        }
    }
}
