---
name: xhe-dom-corresponding-class
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-static-property-access
  - xhe-element-selection
  - xhe-interface-usage
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Select correct class for each DOM element type
---

# XHE DOM Corresponding Class Selection

## Purpose
Select correct class for each DOM element type

## When to Use
- When working with any DOM elements
- When searching or manipulating elements on page

## Input
- HTML element type (anchor, button, input, image, etc.)
- Attributes for element search

## Result
Correctly selected XHE class for element interaction

## Rules

### Basic Principles
1. **Never use** `DOM::$element` for object search
2. **Always use** corresponding class for each DOM element type
3. **Check class method signature** to determine correct class
4. **Never use** variable names matching XHE global variables (e.g., `$anchor`, `$input`, `$button`)
5. **Use** variable names related to business objects (e.g., `$exampleLink`, `$usernameInput`, `$submitButton`)

### Class Correspondence

| HTML Element | XHE Class | Usage Example |
|-------------|-----------|---------------------|
| `<a>` (anchor) | `DOM::$anchor` | `DOM::$anchor->get_by_href()` |
| `<button>` | `DOM::$button` | `DOM::$button->get_by_attribute()` |
| `<form>` | `DOM::$form` | `DOM::$form->get_by_name()` |
| `<input type="text">` | `DOM::$input` | `DOM::$input->get_by_name()` |
| `<input type="button">` | `DOM::$button` | `DOM::$button->get_by_attribute()` |
| `<input type="radio">` | `DOM::$radiobox` | `DOM::$radiobox->get_by_name()` |
| `<input type="checkbox">` | `DOM::$checkbox` | `DOM::$checkbox->get_by_name()` |
| `<input type="file">` | `DOM::$inputfile` | `DOM::$inputfile->get_by_name()` |
| `<input type="image">` | `DOM::$inputimage` | `DOM::$inputimage->get_by_name()` |
| `<input type="hidden">` | `DOM::$hiddeninput` | `DOM::$hiddeninput->get_by_name()` |
| `<img>` | `DOM::$image` | `DOM::$image->get_by_src()` |
| `<div>` | `DOM::$div` | `DOM::$div->get_by_attribute()` |
| `<span>` | `DOM::$span` | `DOM::$span->get_by_attribute()` |
| `<p>` | `DOM::$p` | `DOM::$p->get_by_inner_text()` |
| `<h1>`, `<h2>`, etc. | `DOM::$h1`, `DOM::$h2`, etc. | `DOM::$h1->get_by_inner_text()` |

### Usage Examples

#### Example 1: Working with Anchor
```php
$exampleLink = DOM::$anchor->get_by_href("https://example.com");
if ($exampleLink->is_exist()) {
    $exampleLink->click();
}
```

#### Example 2: Working with Input
```php
$usernameInput = DOM::$input->get_by_name("username");
if ($usernameInput->is_exist()) {
    $usernameInput->set_value("testuser");
}
```

#### Example 3: Working with Checkbox
```php
$rememberMeCheckbox = DOM::$checkbox->get_by_name("remember_me");
if ($rememberMeCheckbox->is_exist()) {
    $rememberMeCheckbox->set_checked(true);
}
```

#### Example 4: Working with Image
```php
$logoImage = DOM::$image->get_by_alt("logo");
if ($logoImage->is_exist()) {
    echo("Image found\n");
}
```

#### Example 5: Working with Button
```php
$submitButton = DOM::$button->get_by_attribute('class', 'submit', true);
if ($submitButton->is_exist()) {
    $submitButton->click();
}
```

## Important Notes

- Always use corresponding class for element type
- Do not use `DOM::$element` for searching any objects
- For complex cases check `type` attribute of input elements
- **Never use** variable names matching XHE global variables
  - Wrong: `$anchor = DOM::$anchor->get_by_href(...)`
  - Correct: `$exampleLink = DOM::$anchor->get_by_href(...)`
  - Wrong: `$input = DOM::$input->get_by_name(...)`
  - Correct: `$usernameInput = DOM::$input->get_by_name(...)`
- Use **camelCase** for variable names with prefix describing business object

## Related Skills
- `xhe-static-property-access` - for class access
- `xhe-element-selection` - for element selection methods
- `xhe-interface-usage` - for working with selection results