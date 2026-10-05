---
description: Correct access to XHE classes via static properties
---

## Purpose
Correct access to XHE classes via static properties

## When to use
- When working with any XHE classes (DOM, WEB, SYSTEM, VISION, WINDOW)
- When accessing XHE API functionality

## Input
- Class name (DOM, WEB, SYSTEM, VISION, WINDOW)
- Name of the desired functional component

## Result
Correctly calling XHE API methods without creating objects

## 📋 Rules

### Basic principles
1. **No instantiation**: No need to create XHE class objects
2. **Static properties**: All XHE classes are accessible as static properties
3. **Access structure**: `CLASS::$property->method()`

### Available classes
- `DOM` - for working with DOM elements
- `WEB` - for working with the browser
- `SYSTEM` - for system operations
- `VISION` - for working with visual recognition
- `WINDOW` - for working with windows

### Usage Examples

#### Example 1: Accessing the Browser
```php
WEB::$browser->navigate("https://example.com");
WEB::$browser->wait_js();
```

#### Example 2: Accessing DOM Elements
```php
$anchor = DOM::$anchor->get_by_href("https://example.com");
$input = DOM::$input->get_by_name("username");
```

#### Example 3: Accessing system functions
```php
SYSTEM::$folder->create_folder("new_folder");
```

#### Example 4: Accessing windows
```php
WINDOW::$app->quit();
```

### Important Notes

- Never use `new DOM()` or similar
- Always access via static properties (`WEB::$webpage`, not a file from explorer)
- Do not read `Templates/Objects` to obtain webpage — init.php already loaded it

## ⚠️ Restrictions

- **Prohibited** creating XHE class objects
- **Prohibited** explorer_tool on Templates/Objects / xhe_webpage.php

## 🔗 Related Skills
- `xhe-initialization` - for initializing access to classes
- `xhe-element-selection` - for selecting DOM elements
- `xhe-dom-corresponding-class` - for selecting the correct DOM element class