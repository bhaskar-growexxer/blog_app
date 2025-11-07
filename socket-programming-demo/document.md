# WebSocket Server Documentation

This document explains the full working cf the `ws-server.php` implementation, including all functions, how the server operates, how rooms and users are managed, and how WebSocket communication works.

---

## ✅ Overview

This server implements a **pure PHP WebSocket server** without external libraries or Composer. It supports:

* WebSocket handshake
* Bi-directional real-time messaging
* Chat rooms (multiple isolated rooms)
* Usernames
* Broadcasting inside rooms
* JSON-based communication
* Clean WebSocket framing (encode/decode)

---

## ✅ Server Startup

The server starts using:

```bash
php ws-server.php
```

It runs on:

```
ws://0.0.0.0:9001
```

---

## ✅ Data Structure for Clients

```php
$clients = [
    (int)$clientId => [
        "socket" => resource,
        "room"   => string,
        "name"   => string,
        "handshake" => bool
    ]
];
```

This allows the server to keep track of:

* Connected socket
* Room ID
* Username
* Handshake state

---

# ✅ Function Documentation

Below is a clear explanation of every major function used in the WebSocket server.

---

## ✅ 1. `ws_encode($message)`

### **Purpose:**

Encodes a plain string message into a WebSocket frame.

### **How it Works:**

* Adds WebSocket opcode `0x81` (text frame)
* Appends message length
* Appends raw payload

---

## ✅ 2. `ws_unmask($payload, $mask)`

### **Purpose:**

Client-to-server frames are masked. This function un-masks the message.

### **How it Works:**

Each byte of payload is XOR'd with one of the 4 mask bytes.

---

## ✅ 3. `ws_decode($data)`

### **Purpose:**

Decodes incoming WebSocket frames from clients.

### **Handles:**

* FIN bit
* Opcode
* Masking
* Payload length (normal, 126, 127)
* Extracts message

This produces the **actual JSON string** sent by the browser.

---

## ✅ 4. `ws_handshake($client, $headers)`

### **Purpose:**

Perform WebSocket handshake after initial TCP connection.

### **Steps:**

1. Extract `Sec-WebSocket-Key`
2. Append GUID and SHA1 + Base64 encode
3. Return `101 Switching Protocols` response

Without this handshake, browser won't upgrade to WebSocket.

---

## ✅ 5. `send_to_room($clients, $roomId, $msg)`

### **Purpose:**

Broadcast a message to all users in a specific chat room.

### **How it Works:**

Loops all clients, checks:

```php
if ($c["room"] === $roomId)
```

And sends encoded WebSocket frame.

---

# ✅ Main Server Loop Documentation

The server runs an infinite loop handling:

### ✅ 1. New client connections

### ✅ 2. Reading data from sockets

### ✅ 3. Running handshake for new clients

### ✅ 4. Processing JSON messages

### ✅ 5. Sending messages to rooms

---

## ✅ Join Event Handling

When browser sends:

```json
{
  "type": "join",
  "name": "bhaskar",
  "room": "123456"
}
```

The server:

1. Stores the name and room
2. Sends system message to the room
3. Prevents invalid joins

---

## ✅ Message Event Handling

When browser sends:

```json
{
  "type": "message",
  "msg": "Hello everyone!"
}
```

Server:

* Ensures user joined a room
* Sends message to every member in that room

---

## ✅ Error Prevention Logic

The server includes guards for:

* Missing room ID
* Missing message body
* Messages before joining room
* Empty or malformed JSON

These prevent PHP notices and undefined index warnings.

---

# ✅ Communication Flow (Step-By-Step)

### ✅ Step 1: Browser opens WebSocket

### ✅ Step 2: Server performs handshake

### ✅ Step 3: User joins room

### ✅ Step 4: Messages start flowing

### ✅ Step 5: Server broadcasts only to room users

---

# ✅ JSON Structures Used

### ✅ Join Room

```json
{
  "type": "join",
  "name": "UserName",
  "room": "123456"
}
```

### ✅ Message

```json
{
  "type": "message",
  "msg": "Hello!"
}
```

### ✅ System Broadcast

```json
{
  "type": "system",
  "msg": "User joined the room"
}
```

---

# ✅ Advantages of This WebSocket Server

* Persistent TCP connection (faster than HTTP)
* Real-time messaging
* Lightweight (no dependencies)
* Highly customizable
* Uses standard WebSocket protocol

---

# ✅ Next Steps

If needed, we can add:

* Typing indicators
* User list per room
* Private chat
* Message histo
