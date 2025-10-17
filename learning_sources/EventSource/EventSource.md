# EventSource in Angular

**EventSource** in Angular is used to handle **Server-Sent Events (SSE)** — a way to receive real-time updates from the server over HTTP.

This is useful for building Angular applications that require live data feeds such as notifications, logs, or dashboards.

---

## Overview

`EventSource` is a native browser API. In Angular, it can be used to subscribe to a stream of server events. The server continuously sends updates to the client via an open connection.

---

## Key Concepts

* **One-way communication**: Server ➜ Angular application.
* **Persistent connection**: Keeps an HTTP connection open.
* **Auto-reconnect**: Tries to reconnect automatically if the connection is lost.
* **Low overhead**: More efficient than polling for regular updates.

---

## Angular Integration Notes

* **Use in services**: Typically, `EventSource` is managed inside an Angular service to keep logic reusable and injectable across components.
* **Zone handling**: SSE callbacks run outside Angular’s zone, so you may need to trigger change detection manually.
* **Unsubscribing**: Close the connection manually to avoid memory leaks when components are destroyed.

---

## Common Use Cases

* Real-time system logs or audit trails
* Live notifications (e.g., chat, task updates)
* Monitoring tools or dashboards
* Streaming event logs or analytics

---

## Best Practices

* Wrap `EventSource` in an Angular service for better separation of concerns.
* Use Angular’s `NgZone` if updates should trigger UI changes.
* Always call `.close()` when done to prevent hanging connections.
* Handle reconnection logic gracefully, especially if the server is down or restarted.

---

## Limitations

* Works only for server-to-client messages.
* Requires the server to support the `text/event-stream` MIME type.
* May need **CORS headers** if connecting to another domain.
* Not all browsers fully support SSE (e.g., limited support on older IE or some mobile browsers).

---

# use case 
const eventSource = new EventSource('https://example.com/events');

eventSource.onmessage = function(event) {
  console.log('New message:', event.data);
};

eventSource.onerror = function(error) {
  console.error('EventSource failed:', error);
};
