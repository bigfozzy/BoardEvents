---
description: Use XHEInterfaces rule
---

## Use XHEInterfaces Rule

### Overview
Each class name that has prefix `XHE` has methods with prefix `get_all_by_` or full method name `get_all` that return an object of type `XHEInterfaces`. The `elements` field of `XHEInterfaces` is an array of objects of the same type as the class.

### Main Principles
- Methods with prefix `get_all_by_` return `XHEInterfaces` objects
- Each element in the `elements` array is of type `XHEInterface`
- The `XHEInterfaces` type implements ArrayAccess, IteratorAggregate, Countable
- Use the `count()` method to get the number of elements

### Examples of get_all_by_ Methods

#### Example 1: XHEAnchor get_all_by_attribute
```php
// Get all anchors by attribute
$attributeName = 'id';
$attributeValue = 'id1';
$anchorInterfaces = DOM::$anchor->get_all_by_attribute($attributeName, $attributeValue);
```

#### Example 2: XHEInput get_all_by_name
```php
// Get all inputs by name
$inputName = 'username';
$exactly = true;
$inputInterfaces = DOM::$input->get_all_by_name($inputName, $exactly);
```

### Using XHEInterfaces as Collection

#### Example: Iterating through XHEInterfaces
```php
$inputName = 'username';
$exactly = true;
$inputInterfaces = DOM::$input->get_all_by_name($inputName, $exactly);
echo("Found " . $inputInterfaces->count() . " input elements\n");
foreach($inputInterfaces as $index => $inputInterface){
    echo("Input #$index has value = '" . $inputInterface->get_value(). "'\n");
}
```

### Important Notes
- For object of type `XHEInterfaces`, use the `count()` method to get the number of elements
- The `XHEInterfaces` object can be used in foreach loops directly
- Each element in the collection is an `XHEInterface` object with all its methods