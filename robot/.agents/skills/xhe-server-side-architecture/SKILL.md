---
name: xhe-server-side-architecture
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-initialization
  - xhe-static-property-access
  - xhe-element-waiting
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Understanding XHE API architecture as a client-server system
---

# XHE Server-Side Architecture

## Purpose
Understanding XHE API architecture as a client-server system

## When to Use
- When developing robots on XHE API
- When understanding command execution flows
- When working with dynamic content

## Input
- None (conceptual understanding)

## Result
Correct understanding of how XHE API works

## Rules

### Basic Principles

#### 1. PHP as Client Code
PHP is used to write client code that sends commands to server.

```php
// This code executes on client
$anchor = DOM::$anchor->get_by_href("https://example.com");
```

#### 2. Server Processes DOM
Server can directly manipulate web page DOM in browser.

```php
// Command sent to server, server manipulates DOM
WEB::$browser->navigate("https://example.com");
```

#### 3. Command Structure
Send only existing commands implemented in classes with `XHE` prefix.

```php
// Correct - existing method
$anchor->click();

// Incorrect - non-existent method
$anchor->non_existent_method();
```

#### 4. Headless Browser
Server uses headless browser for loading, parsing and interacting with web pages.

#### 5. Logic Resolution on Server
All logic, including dynamic page processing, is resolved on server before sending response.

### Execution Flow

```
Client (PHP)                    Server (XHE)
    |                                |
    |--1. navigate(url)------------->|
    |                                |-- Load page
    |                                |-- Parse DOM
    |                                |-- Wait JS
    |                                |
    |<---2. Completion status--------|
    |                                |
    |--3. get_by_id('btn')---------->|
    |                                |-- Search element
    |                                |
    |<---4. XHEInterface object-------|
    |                                |
    |--5. is_exist()---------------->|
    |                                |-- Check existence
    |                                |
    |<---6. true/false---------------|
    |                                |
    |--7. click()-------------------->|
    |                                |-- Click element
    |                                |-- Wait response
    |                                |
    |<---8. true/false---------------|
    |                                |
```

### Usage Examples

#### Example 1: Simple Navigation
```php
// Client sends navigation command
$pageUrl = "https://example.com";
echo("Navigate to: $pageUrl\n");
WEB::$browser->navigate($pageUrl);

// Server loads page, parses DOM, waits JS
WEB::$browser->wait_js();

// Client continues
echo("Page loaded\n");
```

#### Example 2: Working with Dynamic Content
```php
// Client requests navigation
WEB::$browser->navigate("https://example.com/dynamic");
WEB::$browser->wait_js();

// Server already processed dynamic content
// Client can immediately work with elements
$button = DOM::$button->get_by_id('dynamic-btn');
if ($button->is_exist()) {
    // Server already knows if element exists
    $button->click();
}
```

#### Example 3: Waiting for Element to Appear
```php
// Client requests waiting
if (DOM::$div->wait_element_exist_by_id('content')) {
    // Server waited until element appeared
    // Client receives true only when element exists
    $content = DOM::$div->get_by_id('content');
    echo("Content loaded\n");
} else {
    // Server didn't find element during wait time
    echo("Content not found\n");
}
```

#### Example 4: Working with Frames
```php
// Client specifies frame
$frameNumber = 0;
$input = DOM::$input->get_by_name('username', true, $frameNumber);

// Server finds element in specified frame
if ($input->inner_number > -1) {
    $input->set_value('testuser');
}
```

### Important Notes

#### Dynamic Processing
```php
// Client unaware of dynamic content
WEB::$browser->navigate("https://example.com/ajax-page");

// Server automatically waits for AJAX content to load
WEB::$browser->wait_js();

// Now client can work with loaded content
$dynamicElement = DOM::$div->get_by_id('ajax-result');
```

#### Asynchronous Operations
```php
// All operations are synchronous for client
$button->click();  // Waits for click completion
WEB::$browser->wait_js();  // Waits for page load

// Server handles asynchrony internally
```

#### Error Handling
```php
// Server returns operation status
$result = $button->click();
if (!$result) {
    echo("Click failed\n");
    // Server already knows why it failed
}
```

### Architectural Patterns

#### 1. Command Pattern
Each XHE class method call sends a command to server.

#### 2. Proxy Pattern
XHE classes on client act as proxy for real objects on server.

#### 3. XHEInterface Interface
Represents remote DOM element, all operations sent to server.

### Architecture Benefits

1. **Centralized Logic**: All DOM operation logic on server
2. **Synchronous API**: Simple synchronous interface for client
3. **Automatic Processing**: Server automatically handles dynamics
4. **Isolation**: Client isolated from browser details

## Important Notes

- PHP client code does not manipulate DOM directly
- All DOM operations execute on server
- Server automatically handles dynamic content
- Each method call sends command to server
- Use only existing XHE class methods

## Related Skills
- `xhe-initialization` - for connection initialization
- `xhe-static-property-access` - for class access
- `xhe-element-waiting` - for dynamic content waiting