---
name: xhe-element-waiting
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies: []
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Waiting for DOM elements to appear on page
related_skills:
  - xhe-element-selection
  - xhe-interface-usage
  - xhe-dom-corresponding-class
---

# XHE Element Waiting Pattern

## Purpose
Waiting for DOM elements to appear on page

## When to Use
- When working with dynamic content
- After navigation or asynchronous operations
- When element may appear with delay

## Input
- Element search criteria
- (Optional) frame number

## Result
Waiting code that continues execution only after element appears

## Rules

### Element Waiting Methods

All methods have prefix `wait_element_exist_by_` and return `bool`

#### 1. By ID
```php
$elementClass->wait_element_exist_by_id($id, $exactly = true, $frame = "-1")
```

#### 2. By Name
```php
$elementClass->wait_element_exist_by_name($name, $frame = "-1")
```

#### 3. By Attribute
```php
$elementClass->wait_element_exist_by_attribute($attr_name, $attr_value, $exactly = true, $frame = "-1")
```

#### 4. By Number
```php
$elementClass->wait_element_exist_by_number($number, $frame = "-1")
```

#### 5. By inner HTML
```php
$elementClass->wait_element_exist_by_inner_html($inner_html, $exactly = true, $frame = "-1")
```

#### 6. By outer HTML
```php
$elementClass->wait_element_exist_by_outer_html($outer_html, $exactly = true, $frame = "-1")
```

#### 7. By XPath
```php
$elementClass->wait_element_exist_by_xpath($xpath)
```

### Usage Examples

#### Example 1: Wait for Element by ID
```php
// Wait for button with ID "submit-btn"
if (DOM::$button->wait_element_exist_by_id('submit-btn')) {
    $submitButton = DOM::$button->get_by_id('submit-btn');
    $submitButton->click();
} else {
    echo("Button not found\n");
}
```

#### Example 2: Wait for Element by Name
```php
// Wait for input with name "username"
if (DOM::$input->wait_element_exist_by_name('username')) {
    $usernameInput = DOM::$input->get_by_name('username', true);
    $usernameInput->set_value('testuser');
}
```

#### Example 3: Wait for Element by Attribute
```php
// Wait for link with class "dynamic-link"
if (DOM::$anchor->wait_element_exist_by_attribute('class', 'dynamic-link', true)) {
    $dynamicLink = DOM::$anchor->get_by_attribute('class', 'dynamic-link', true);
    $dynamicLink->click();
}
```

#### Example 4: Wait for Element by Number
```php
// Wait for first div on page
if (DOM::$div->wait_element_exist_by_number(0)) {
    $firstDiv = DOM::$div->get_by_number(0);
    echo("First div found\n");
}
```

#### Example 5: Wait for Element in Frame
```php
// Wait for input in frame
$frameNumber = 0;
if (DOM::$input->wait_element_exist_by_name('search-input', $frameNumber)) {
    $searchInput = DOM::$input->get_by_name('search-input', true, $frameNumber);
    $searchInput->set_value('search term');
}
```

#### Example 6: Wait by inner HTML
```php
// Wait for element with specific inner HTML
if (DOM::$div->wait_element_exist_by_inner_html('<p>Hello World</p>', true)) {
    $contentDiv = DOM::$div->get_by_inner_html('<p>Hello World</p>', true);
    echo("Div with content found\n");
}
```

#### Example 7: Wait by XPath
```php
// Wait for element by XPath
if (DOM::$element->wait_element_exist_by_xpath('//div[@class="container"]/button')) {
    echo("Element by XPath found\n");
}
```

#### Example 8: Full Scenario with Navigation
```php
// Navigate to page
$pageUrl = "https://example.com/dynamic-page";
echo("Navigate to: $pageUrl\n");
WEB::$browser->navigate($pageUrl);
WEB::$browser->wait_js();

// Wait for dynamic content to appear
if (DOM::$div->wait_element_exist_by_attribute('id', 'dynamic-content', true)) {
    $contentDiv = DOM::$div->get_by_attribute('id', 'dynamic-content', true);
    echo("Dynamic content loaded\n");
} else {
    echo("Dynamic content failed to load\n");
}
```

#### Example 9: Wait with Error Handling
```php
$submitButtonName = 'submit-button';
echo("Waiting for button with name: $submitButtonName\n");

if (DOM::$button->wait_element_exist_by_name($submitButtonName)) {
    $submitButton = DOM::$button->get_by_name($submitButtonName, true);
    echo("Button found, clicking...\n");
    $submitButton->click();
    echo("Button clicked\n");
} else {
    echo("ERROR: Button '$submitButtonName' not found\n");
    // Error handling
    exit(1);
}
```

### Method Arguments

#### exactly (bool)
- `true` - exact match (default)
- `false` - partial match

#### frame (string)
- `"-1"` - not in frame (default)
- `"0"`, `"1"`, ... - frame number
- `"0:1"`, `"0:1:2"` - nested frames

### Combining with Navigation

After navigation always wait for page load:
```php
WEB::$browser->navigate($url);
WEB::$browser->wait_js();

// Now you can wait for elements
if (DOM::$anchor->wait_element_exist_by_id('main-link')) {
    // Work with element
}
```

## Important Notes

- Always check return value of waiting method
- Use waiting for dynamic content
- After navigation always call `WEB::$browser->wait_js()`
- For elements in frames specify frame number

## Related Skills
- `xhe-element-selection` - for search method selection
- `xhe-interface-usage` - for working with found elements
- `xhe-dom-corresponding-class` - for correct class selection