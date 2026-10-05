---
name: xhe-interface-usage
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
    - xhe-element-selection
    - xhe-dom-corresponding-class
    - xhe-element-waiting
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Proper work with XHEInterface and XHEInterfaces objects
---

# XHE Interface Usage Pattern

## Purpose
Proper work with XHEInterface and XHEInterfaces objects

## When to Use
- After retrieving element through `get_by_` or `get_all_by_` methods
- When checking element existence
- When working with element attributes and methods

## Input
- Object of type `XHEInterface` or `XHEInterfaces`
- Operation type (existence check, get attribute, click, etc.)

## Result
Correct interaction with DOM elements

## Rules

### Return Types

#### 1. XHEInterface (for single elements)
- Methods with prefix `get_by_` return `XHEInterface`
- Represents one DOM element
- Even if element not found, `XHEInterface` object is returned

#### 2. XHEInterfaces (for multiple elements)
- Methods with prefix `get_all_by_` return `XHEInterfaces`
- Contains array of elements in `elements` field
- Implements interfaces: ArrayAccess, IteratorAggregate, Countable

### XHEInterface Methods

All XHEInterface methods are available through objects obtained via static properties of DOM classes (e.g., `DOM::$anchor->get_by_href()` returns XHEInterface object).

#### Basic Methods
```php
// Get attribute value
$elementInterface->get_attribute($attributeName);

// Set attribute value
$elementInterface->set_attribute($attributeName, $value);

// Click element
$elementInterface->click();

// Set focus on element
$elementInterface->focus();

// Check element existence
$elementInterface->is_exist();

// Get inner text
$elementInterface->get_inner_text();

// Get inner HTML
$elementInterface->get_inner_html();

// Get value (for input)
$elementInterface->get_value();

// Set value (for input)
$elementInterface->set_value($value);
```

### Checking Element Existence

#### Method 1: is_exist() Method
```php
$exampleLink = DOM::$anchor->get_by_href("https://example.com");
if ($exampleLink->is_exist()) {
    echo("Anchor found\n");
    $exampleLink->click();
}
```

#### Method 2: Check inner_number
```php
$usernameInput = DOM::$input->get_by_name('username', true);
if ($usernameInput->inner_number > -1) {
    echo("Input found\n");
    $usernameInput->set_value("test");
}
```

### Working with XHEInterfaces

#### Iterating Elements
```php
$usernameInputs = DOM::$input->get_all_by_name('username', true);
echo("Found " . $usernameInputs->count() . " input elements\n");

foreach($usernameInputs as $index => $inputInterface){
    echo("Input #$index has value = '" . $inputInterface->get_value() . "'\n");
}
```

#### Getting Element Count
```php
$allDivs = DOM::$div->get_all_by_attribute('class', 'content');
$count = $allDivs->count();
echo("Found $count div elements\n");
```

### Usage Examples

#### Example 1: Getting Element Attributes
```php
$myLink = DOM::$anchor->get_by_attribute('id', 'myLink');
if ($myLink->is_exist()) {
    $href = $myLink->get_attribute('href');
    echo("Anchor href: '$href'\n");
}
```

#### Example 2: Setting Attribute
```php
$submitButton = DOM::$button->get_by_name('submit');
if ($submitButton->is_exist()) {
    $submitButton->set_attribute('disabled', 'true');
}
```

#### Example 3: Clicking Element
```php
$clickableLink = DOM::$anchor->get_by_inner_text('Click me', true);
if ($clickableLink->is_exist()) {
    $clickableLink->click();
}
```

#### Example 4: Working with Input
```php
$usernameInput = DOM::$input->get_by_name('username', true);
if ($usernameInput->is_exist()) {
    $usernameInput->set_value('testuser');
    $value = $usernameInput->get_value();
    echo("Input value: '$value'\n");
}
```

#### Example 5: Working with Checkbox
```php
$rememberMe = DOM::$checkbox->get_by_name('remember_me', true);
if ($rememberMe->is_exist()) {
    $rememberMe->set_checked(true);
    $isChecked = $rememberMe->is_checked();
    echo("Checkbox is checked: $isChecked\n");
}
```

#### Example 6: Working with Multiple Elements
```php
$externalLinks = DOM::$anchor->get_all_by_attribute('class', 'external-link');
foreach($externalLinks as $index => $externalLink) {
    $href = $externalLink->get_attribute('href');
    echo("External link #$index: $href\n");
}
```

## Important Notes

- Always check element existence before operations
- `is_exist()` is preferred over checking `inner_number`
- For multiple elements use `count()` to get count
- Can use `foreach` directly to iterate `XHEInterfaces`

## Related Skills
- `xhe-element-selection` - for retrieving elements
- `xhe-dom-corresponding-class` - for correct class selection
- `xhe-element-waiting` - for waiting for elements to appear
- `xhe-static-property-access` - for correct access to XHE classes