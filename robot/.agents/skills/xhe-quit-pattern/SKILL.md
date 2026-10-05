---
name: xhe-quit-pattern
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-initialization
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Proper XHE server shutdown
---

# XHE Quit Pattern

## Purpose
Proper XHE server shutdown

## When to Use
- At end of each PHP script
- After completing all robot operations

## Input
- None (termination pattern)

## Result
Proper termination of all server-side processes

## Rules

### Mandatory Termination Code
```php
// Quit the application
WINDOW::$app->quit();
```

### Basic Principles
1. **Placement**: Termination code must be at very end of PHP script
2. **Mandatory**: This code is mandatory for all XHE API PHP scripts
3. **Goal**: Stop all server-side processes

## Lifecycle

1. Develop robot logic
2. Add termination code to end of file
3. Ensure all resources released
4. Terminate work

## Important Notes

- Without this code server processes may continue working
- Always add this code to end of file
- Code should be last statement in script

## Related Skills
- `xhe-initialization` - for script initialization