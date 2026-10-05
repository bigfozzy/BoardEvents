---
name: xhe-initialization
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-quit-pattern
  - xhe-static-property-access
  - xhe-element-selection
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Proper XHE API initialization in PHP scripts
---

# XHE Initialization Pattern

## Purpose
Proper XHE API initialization in PHP scripts

## When to Use
- When creating new PHP script for XHE API work
- At beginning of each robot code file

## Input
- None (initialization pattern)

## Result
Correctly initialized PHP script with XHE API access

## Rules

### Mandatory Initialization Code

If Sticky `require=` is set, copy that line. The project already has `init.php` (or a shim that loads Studio Templates).

```php
$xhe_host = getenv("RPABOT_HOST_URL");
require_once(__DIR__ . "/init.php");
```

Use `__DIR__ . "/../../Templates/init.php"` only when there is **no** `init.php` in the robot folder (Test Samples layout). From `%TEMP%` or a Studio project root that path does not exist.

### Basic Principles
1. **Placement**: Initialization code must be at very beginning of each PHP script
2. **Mandatory**: This initialization is mandatory for all XHE API operations
3. **Path to init.php**: Sticky `require=` / `__DIR__ . "/init.php"` in the robot folder. Not `../../Templates` unless that file is actually two levels up.
4. **Host**: Default is call function `getenv("RPABOT_HOST_URL")`

### Limitations
- After init.php **do** use `WEB::$webpage`, `WEB::$browser`, `DOM::$anchor` — they are the API.
- **Forbidden** to `new XHEWebPage()` or to open `Templates/Objects/*.php` via explorer to «get» a webpage object.

## Lifecycle

1. Create new PHP file
2. Add initialization code to beginning of file
3. Develop robot logic
4. Add termination code (see skill `xhe-quit-pattern`)

## Important Notes

- After initialization all classes available: `DOM`, `SYSTEM`, `WEB`, `VISION`, `WINDOW`
- All XHE classes accessible as static properties
- No need to create XHE class objects

## Related Skills
- `xhe-quit-pattern` - for proper termination
- `xhe-static-property-access` - for class access
- `xhe-element-selection` - for DOM element work