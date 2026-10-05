---
description: Use XHEInterface rule
---

## Use XHEInterface Rule

### Overview
Each class name that has prefix `XHE` has methods with prefix `get_by_` return an object of type `XHEInterface` even if element not has be found.

### Main Principles
- Methods with prefix `get_by_` always return `XHEInterface` objects
- `XHEInterface` represents a single DOM element
- Any DOM XHE class implements `XHEBaseDOMVisual` and all its methods

### Examples of get_by_ Methods

#### Example 1: XHEAnchor get_by_attribute
```php
// Get anchor by attribute and return XHEInterface
$attributeName = 'id';
$attributeValue = 'id1';
$targetAnchorInterface = DOM::$anchor->get_by_attribute($attributeName, $attributeValue);
$hrefAttributeName = 'href';
echo("Get target Anchor href attribute: '" . $targetAnchorInterface->get_attribute($hrefAttributeName) . "'\n");
```

#### Example 2: XHEInput get_by_name
```php
// Get input by name and return XHEInterface
$inputName = 'username';
$exactly = true;
$targetInputInterface = DOM::$input->get_by_name($inputName, $exactly);
$valueAttributeName = 'value';
echo("Get target Input value attribute: '" . $targetInputInterface->get_attribute($valueAttributeName) . "'\n");
```

### XHEInterface Methods
- `get_attribute($attributeName)` - Get attribute value
- `set_attribute($attributeName, $value)` - Set attribute value
- `click():bool` - Click on element
- `focus():bool` - Set focus to element
- `is_exist():bool` - Check if element exists on WEB page
- For other methods, see the documentation.

### XHEIntreface check element is exists on WEB page
- Check is element exists on page for object of type `XHEInterface` call function `is_exist():bool`.
- Alternative check is element exists on page for object of type `XHEInterface` check field `inner_number` value, if `inner_number` value greater than -1 then element is existed.