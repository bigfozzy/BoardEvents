---
description: for DOM elements use corrspond class rule
---

## DOM Elements Corresponding Class Rule

### Overview
Check for each DOM class method signature and use the appropriate corresponding class for each DOM element type.

### Main Principles
1. Do not use DOM::$element to search for any object. Instead, use the corresponding object for each DOM element type.
2. **Never use variable names that conflict with XHE global variables** (e.g., `$anchor`, `$input`, `$button`)
3. **Always use business-object-related variable names** (e.g., `$exampleLink`, `$usernameInput`, `$submitButton`)

### DOM Element Classes
- For anchor use DOM::$anchor
- For button use DOM::$button
- For form use DOM::$form
- For input use DOM::$input
- For input 'type' = radio, use DOM::$radiobox
- For input type='button', use DOM::$button
- For input type="checkbox", use DOM::$checkbox
- For image use DOM::$image, and so on

### Examples
#### Example 1: Working with anchor element
```php
// Get anchor by href attribute
$exampleLink = DOM::$anchor->get_by_href("https://example.com");
```

#### Example 2: Working with input element
```php
// Get input by name
$usernameInput = DOM::$input->get_by_name("username");
```

#### Example 3: Working with checkbox element
```php
// Get checkbox by name
$rememberMeCheckbox = DOM::$checkbox->get_by_name("remember_me");
```

### Variable Naming Rules
- ❌ **Incorrect**: `$anchor = DOM::$anchor->get_by_href(...)`
- ✅ **Correct**: `$exampleLink = DOM::$anchor->get_by_href(...)`
- ❌ **Incorrect**: `$input = DOM::$input->get_by_name(...)`
- ✅ **Correct**: `$usernameInput = DOM::$input->get_by_name(...)`
- ❌ **Incorrect**: `$button = DOM::$button->get_by_attribute(...)`
- ✅ **Correct**: `$submitButton = DOM::$button->get_by_attribute(...)`

**Always use camelCase variable names that describe the business object's purpose in the application logic.**
