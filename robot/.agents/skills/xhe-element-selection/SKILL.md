---
name: xhe-element-selection
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies:
  - xhe-dom-corresponding-class
  - xhe-interface-usage
  - xhe-element-waiting
changelog:
  - "1.0.0-stable: Initial stable release"
  - "0.9.0-dev: Beta testing"
description: Select correct method for searching DOM elements
---

# XHE Element Selection Methods

## Purpose
Select correct method for searching DOM elements

## When to Use
- When searching elements on page
- When working with frames
- When need to choose between get_by_ and get_all_by_ methods

## Input
- HTML element type
- Search criteria (attribute, name, number, etc.)
- Frame work requirement

## Result
Correctly selected method for element search

## Rules

### Basic Principles

#### 1. Single Element Methods (get_by_)
- Return object of type `XHEInterface`
- Used when need one specific element
- Methods: `get_by_id()`, `get_by_name()`, `get_by_attribute()`, `get_by_number()`, `get_by_href()`, etc.

#### 2. Multiple Element Methods (get_all_by_)
- Return object of type `XHEInterfaces`
- Used when need to work with multiple elements
- Methods: `get_all_by_id()`, `get_all_by_name()`, `get_all_by_attribute()`, `get_all_by_number()`, etc.

### Working with Frames

#### frame Argument
```php
get_by_name($name, $exactly, $frame)
```

Values for `frame` argument:
1. **-1** (default) - element not in frame
2. **0, 1, 2, ...** - frame number on page (0-based)
3. **"0:1"** - frame number and subframe number (colon-separated)

#### Frame Number Variable
Always use variable name `frameNumber` for storing frame number

### Usage Examples

#### Example 1: Search by Name without Frame
```php
$inputName = 'username';
$exactly = true;
$targetInput = DOM::$input->get_by_name($inputName, $exactly);
if ($targetInput->is_exist()) {
    echo("Input found by name = '$inputName'\n");
}
```

#### Example 2: Search by Attribute with Exact Match
```php
$attributeName = 'class';
$attributeValue = 'submit';
$exactly = true;
$targetButton = DOM::$button->get_by_attribute($attributeName, $attributeValue, $exactly);
if ($targetButton->is_exist()) {
    $targetButton->click();
}
```

#### Example 3: Search in Frame
```php
$frameNumber = 0;
$inputName = 'username';
$exactly = true;
$targetInputInFrame = DOM::$input->get_by_name($inputName, $exactly, $frameNumber);
if ($targetInputInFrame->inner_number > -1) {
    echo("Input found in frame = '$frameNumber' by name = '$inputName'\n");
}
```

#### Example 4: Search in Subframe
```php
$frameNumber = "0:1";
$attributeName = 'class';
$attributeValue = 'submit';
$exactly = true;
$targetButtonInSubframe = DOM::$button->get_by_attribute($attributeName, $attributeValue, $exactly, $frameNumber);
if ($targetButtonInSubframe->is_exist()) {
    echo("Button found in subframe\n");
}
```

#### Example 5: Search Multiple Elements
```php
$attributeName = 'class';
$attributeValue = 'link';
$allAnchors = DOM::$anchor->get_all_by_attribute($attributeName, $attributeValue);
echo("Found " . $allAnchors->count() . " anchors\n");
foreach($allAnchors as $index => $anchor){
    echo("Anchor #$index: " . $anchor->get_href() . "\n");
}
```

#### Example 6: Search by Number
```php
$divNumber = 0;
$firstDiv = DOM::$div->get_by_number($divNumber);
if ($firstDiv->is_exist()) {
    echo("First div found\n");
}
```

### Available Search Methods

#### Search by ID
- `get_by_id($id, $exactly = true, $frame = "-1")`
- `get_all_by_id($id, $exactly = true, $frame = "-1")`

#### Search by Name
- `get_by_name($name, $exactly = true, $frame = "-1")`
- `get_all_by_name($name, $exactly = true, $frame = "-1")`

#### Search by Attribute
- `get_by_attribute($attr_name, $attr_value, $exactly = true, $frame = "-1")`
- `get_all_by_attribute($attr_name, $attr_value, $exactly = true, $frame = "-1")`

#### Search by Number
- `get_by_number($number, $frame = "-1")`
- `get_all_by_number($number, $frame = "-1")`

#### Search by Content
- `get_by_inner_text($text, $exactly = true, $frame = "-1")`
- `get_by_inner_html($html, $exactly = true, $frame = "-1")`
- `get_by_outer_html($html, $exactly = true, $frame = "-1")`

## Important Notes

- For elements in frames always specify `frame` argument
- Use `frameNumber` as variable name for frame number
- Check element existence after search
- For multiple elements use `count()` to get count

## Related Skills
- `xhe-dom-corresponding-class` - for correct class selection
- `xhe-interface-usage` - for working with results
- `xhe-element-waiting` - for waiting for elements to appear