---
description: Client side
---

## Server-Side PHP Rule

### Overview
PHP is a server-side language, but in this case PHP used to write client side code to send known commands to the server and get response.

### Main Principles
1. **PHP as Client Code**: PHP is used to write client-side code that sends commands to the server
2. **DOM Manipulation**: The server can directly manipulate the DOM of a webpage in the browser
3. **Command Structure**: Send only existing commands to the server and get response implemented in classes with prefix `XHE` in name
4. **Headless Browser**: The server uses a headless browser to load, parse, and interact with web pages internally
5. **Logic Resolution**: All logic, including dynamic page handling (e.g., waiting for content to load, handling frames), is resolved on the server before sending a response

### Implementation Details
- The PHP code acts as a client that sends commands to the XHE server
- The server processes these commands and returns the results
- All DOM operations are performed server-side
- Dynamic content handling is managed by the server before response is sent