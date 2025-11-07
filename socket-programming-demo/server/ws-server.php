<?php
$host = "0.0.0.0";
$port = 9001;

$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($server, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($server, $host, $port);
socket_listen($server);

$clients = [];

echo "✅ WebSocket Server started at ws://localhost:$port\n";

while (true) {
    $changed = $clients;
    $changed[] = $server;

    socket_select($changed, $write, $except, 0);

    // New client connection
    if (in_array($server, $changed)) {

        $client = socket_accept($server);
        $clients[] = $client;

        // WebSocket handshake
        $header = socket_read($client, 1024);
        handshake($header, $client);

        echo "🔌 New client connected\n";

        $key = array_search($server, $changed);
        unset($changed[$key]);
    }

    // Handle client messages
    foreach ($changed as $sock) {
        $buffer = @socket_read($sock, 2048, PHP_BINARY_READ);

        if (!$buffer) { // client disconnected
            $key = array_search($sock, $clients);
            unset($clients[$key]);
            socket_close($sock);
            echo "❌ Client disconnected\n";
            continue;
        }

        $decoded = unmask($buffer);
        echo "📩 Received: $decoded\n";

        // Broadcast message to all clients
        $response = mask($decoded);
        foreach ($clients as $client) {
            @socket_write($client, $response, strlen($response));
        }
    }
}

// ------------------ FUNCTIONS ------------------

function handshake($headers, $client)
{
    if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $matches)) {
        return;
    }

    $key = trim($matches[1]);

    $acceptKey = base64_encode(
        sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true)
    );

    $upgrade = 
        "HTTP/1.1 101 Switching Protocols\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";

    socket_write($client, $upgrade, strlen($upgrade));
}

function unmask($payload)
{
    $length = ord($payload[1]) & 127;

    if ($length == 126) {
        $mask = substr($payload, 4, 4);
        $data = substr($payload, 8);
    } else if ($length == 127) {
        $mask = substr($payload, 10, 4);
        $data = substr($payload, 14);
    } else {
        $mask = substr($payload, 2, 4);
        $data = substr($payload, 6);
    }

    $text = "";
    for ($i = 0; $i < strlen($data); $i++) {
        $text .= $data[$i] ^ $mask[$i % 4];
    }

    return $text;
}

function mask($text)
{
    $b1 = 0x81; // text frame
    $length = strlen($text);

    if ($length <= 125) {
        $header = pack('CC', $b1, $length);
    } else if ($length <= 65535) {
        $header = pack('CCn', $b1, 126, $length);
    } else {
        $header = pack('CCNN', $b1, 127, 0, $length);
    }

    return $header . $text;
}
