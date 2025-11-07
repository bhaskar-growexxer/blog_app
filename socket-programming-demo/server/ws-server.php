<?php
$host = "0.0.0.0";
$port = 9001;

$server = stream_socket_server("tcp://$host:$port", $errno, $errstr);

if (!$server) {
    die("Error: $errstr ($errno)\n");
}

echo "✅ WebSocket Server started on ws://$host:$port\n";

/**
 * Connected clients
 * [
 *     (int)$clientId => [
 *         "socket" => resource,
 *         "room"   => string,
 *         "name"   => string
 *     ]
 * ]
 */
$clients = [];

/**
 * Encode WebSocket frame
 */
function ws_encode($message) {
    $frame = chr(129); // 10000001 text frame
    $length = strlen($message);

    if ($length <= 125) {
        $frame .= chr($length);
    } elseif ($length <= 65535) {
        $frame .= chr(126) . pack("n", $length);
    } else {
        $frame .= chr(127) . pack("J", $length);
    }

    return $frame . $message;
}

/**
 * Unmask payload
 */
function ws_unmask($payload, $mask) {
    $out = '';
    $len = strlen($payload);
    for ($i = 0; $i < $len; $i++) {
        $out .= $payload[$i] ^ $mask[$i % 4];
    }
    return $out;
}

/**
 * Decode WebSocket frame (clean version)
 */
function ws_decode($data) {
    $bytes = unpack('C*', $data);
    $firstByte  = $bytes[1];
    $secondByte = $bytes[2];

    $opcode = $firstByte & 0x0F;
    $masked = ($secondByte >> 7) & 0x1;
    $payloadLen = $secondByte & 0x7F;

    $offset = 3;

    if ($payloadLen === 126) {
        $payloadLen = ($bytes[3] << 8) + $bytes[4];
        $offset = 5;
    } elseif ($payloadLen === 127) {
        // Handle 64-bit (extremely rare)
        $payloadLen =
            ($bytes[3] << 56) + ($bytes[4] << 48) + ($bytes[5] << 40) +
            ($bytes[6] << 32) + ($bytes[7] << 24) + ($bytes[8] << 16) +
            ($bytes[9] << 8)  + $bytes[10];
        $offset = 11;
    }

    if ($masked) {
        $mask = substr($data, $offset - 1, 4);
        $payload = substr($data, $offset + 3);
        return ws_unmask($payload, $mask);
    }

    return substr($data, $offset - 1);
}

/**
 * Perform WebSocket handshake
 */
function ws_handshake($client, $headers) {
    if (!preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $matches)) {
        return false;
    }

    $key = trim($matches[1]);

    $accept = base64_encode(
        sha1($key . "258EAFA5-E914-47DA-95CA-C5AB0DC85B11", true)
    );

    $upgrade = 
        "HTTP/1.1 101 Switching Protocols\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Accept: $accept\r\n\r\n";

    fwrite($client, $upgrade);
    return true;
}

/**
 * Broadcast message to a room
 */
function send_to_room($clients, $roomId, $msg) {
    foreach ($clients as $id => $c) {
        if ($c["room"] === $roomId) {
            @fwrite($c["socket"], ws_encode($msg));
        }
    }
}

/**
 * MAIN LOOP
 */
while (true) {
    $readSockets = [$server];
    foreach ($clients as $c) {
        $readSockets[] = $c["socket"];
    }

    stream_select($readSockets, $write, $except, 0);

    foreach ($readSockets as $socket) {

        // NEW CLIENT
        if ($socket === $server) {
            $newSocket = stream_socket_accept($server);
            $id = intval($newSocket);
            $clients[$id] = [
                "socket" => $newSocket,
                "room"   => null,
                "name"   => null
            ];
            continue;
        }

        $id = intval($socket);
        $data = @fread($socket, 2048);

        if (!$data) {
            echo "❌ Client disconnected: $id\n";
            unset($clients[$id]);
            continue;
        }

        // HANDSHAKE
        if (!isset($clients[$id]["handshake"])) {
            ws_handshake($socket, $data);
            $clients[$id]["handshake"] = true;
            continue;
        }

        // DECODE PAYLOAD
        $msg = ws_decode($data);
        $json = json_decode($msg, true);

        if (!$json) continue;

        // When user joins room
        if ($json["type"] === "join") {

            $room = $json["room"] ?? null;
            $name = $json["name"] ?? "Unknown";

            if (!$room) {
                echo "❌ Join event with missing room\n";
                continue;
            }

            $clients[$id]["room"] = $room;
            $clients[$id]["name"] = $name;

            echo "✅ $name joined room $room\n";

            send_to_room($clients, $room, json_encode([
                "type" => "system",
                "msg"  => "$name joined room $room"
            ]));

            continue;
        }

        // Chat message
        // Chat message
        if ($json["type"] === "message") {

            // Avoid undefined room notice
            if (!isset($clients[$id]["room"])) {
                echo "❌ Message received before joining room\n";
                continue;
            }

            $room = $clients[$id]["room"];
            $name = $clients[$id]["name"] ?? "Unknown";

            // Avoid undefined msg notice
            $messageText = $json["msg"] ?? "";

            if ($messageText === "") {
                echo "❌ Empty message received\n";
                continue;
            }

            send_to_room($clients, $room, json_encode([
                "type" => "message",
                "name" => $name,
                "msg"  => $messageText
            ]));

            continue;
        }

    }
}
?>
