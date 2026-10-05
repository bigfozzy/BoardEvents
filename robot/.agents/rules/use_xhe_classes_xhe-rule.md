---
description: Use XHE classes rule
---

## Use XHE Classes Rule

### Overview
Use XHE classes rules.

### Main Principles
- XHE classes are accessed as static properties. No object instantiation required
- `frame` argument usage in methods to get DOM element 
- Forbidden use in code global variable names from `/Templates/init.php`

### Frame argument Usage
Use `frame` argument for XHE classes DOM elements only:
1. If a frame is not used, then the аrame argument value is -1 by default
2. If a frame is used, then the frame argument value is the ordinal frame number on the page (0-based)
3. If a frame is used to get element in subframe, then the frame argument value is a colon-separated string of ordinal numbers of the frame and subframe number (as child number in parent frame)

### Variable Naming
For variable that store frame number argument use name `frameNumber`

### Restrictions
Forbidden use in code global variable names from file `/Templates/init.php` for variable name.

### Examples
#### Example 1: Using XHEAnchor
```php
// Get anchor by attribute
$attributeName = 'id';
$attributeValue = 'id1';
$targetAnchor = DOM::$anchor->get_by_attribute($attributeName, $attributeValue);
if ($targetAnchor->is_exist()) {
    echo("Anchor found\n");
}
```

#### Example 2: Working with frames
```php
// Get input element from frame
$frameNumber = 0;
$inputName = 'username';
$exactly = true;
$targetInputInFrame = DOM::$input->get_by_name($inputName, $exactly, $frameNumber);
if ($targetInputInFrame->inner_number > -1) {
    echo("Input found in frame = '$frameNumber' by name = '$inputName'\n");
}
```

#### Example 3: Working with subframes
```php
// Get button element from subframe
$frameNumber = "0:1";
$attributeName = 'class';
$attributeValue = 'submit';
$exactly = true;
$targetButtonInSubframe = DOM::$button->get_by_attribute($attributeName, $attributeValue, $exactly, $frameNumber);
if ($targetButtonInSubframe->is_exist()) {
    echo("Input found in found in subframe\n");
}