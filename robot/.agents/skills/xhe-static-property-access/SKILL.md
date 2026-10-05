---
name: xhe-static-property-access
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-initialization
  - xhe-element-selection
  - xhe-dom-corresponding-class
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Proper access to XHE classes through static properties
---

# XHE Static Property Access Pattern

## Purpose
Proper access to XHE classes through static properties

## When to Use
- When working with any XHE classes (DOM, WEB, SYSTEM, VISION, WINDOW)
- When accessing XHE API functionality

## Input
- Class name (DOM, WEB, SYSTEM, VISION, WINDOW)
- Functional component name needed

## Result
Correct XHE API method call without object instantiation

## Rules

### Basic Principles
1. **No instantiation**: No need to create XHE class objects
2. **Static properties**: All XHE classes accessible as static properties
3. **Access structure**: `CLASS::$property->method()`

### Available Classes
- `DOM` - for DOM element work
- `WEB` - for browser work
- `SYSTEM` - for system operations
- `VISION` - for visual recognition work
- `WINDOW` - for window work

### Usage Examples

#### Example 1: Browser Access
```php
WEB::$browser->navigate("https://example.com");
WEB::$browser->wait_js();
```

#### Example 2: DOM Element Access
```php
$anchor = DOM::$anchor->get_by_href("https://example.com");
$input = DOM::$input->get_by_name("username");
```

#### Example 3: System Function Access
```php
SYSTEM::$folder->create_folder("new_folder");
```

#### Example 4: Window Access
```php
WINDOW::$app->quit();
```

### Important Notes

- Never use `new DOM()` or similar
- Always access via static properties
- `XHE` prefix in class names indicates XHE classes

## Limitations

- **Forbidden** to create XHE class objects
- **Forbidden** to use global variables from `/Templates/init.php`

## Related Skills
- `xhe-initialization` - for class access initialization
- `xhe-element-selection` - for DOM element selection
- `xhe-dom-corresponding-class` - for correct DOM element class selection