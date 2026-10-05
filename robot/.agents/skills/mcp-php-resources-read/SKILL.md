---
name: mcp-php-resources-read
version: 1.0.0-stable
last_updated: 2024-01-15T14:30:00Z
author: system
compatibility: ">=2.0.0"
dependencies: []
changelog:
- "1.0.0-stable: Initial stable release"
description: Reading XHE API MCP documentation resources
---

# MCP PHP Resources Read

## Purpose
Reading XHE API MCP documentation resources via URI

## When to Use
- When to get information about an XHE class
- When to get information about an XHE method
- When to get code examples
- When to get information by category

## Input
Resource URI in the format:
- `php://class/{class_name}` - class information
- `php://method/{class_name}/{method_name}` - method information
- `php://examples/{class_name}/{method_name}` - code examples
- `php://category/{category}` - resources by category

## Result
JSON response with resource contents

## Available Resources

### Class Resource
**URI:** `php://class/{class_name}`

**Example:**
```
php://class/XHEExcel
```

**Result:**
- Class Name
- Category
- Description
- Inheritance
- Static Access

### Method Resource
**URI:** `php://method/{class_name}/{method_name}`

**Example:**
```
php://method/XHEExcel/open
```

**Result:**
- Signature Method
- Description
- Return Type
- Parameters

### Examples Resource
**URI:** `php://examples/{class_name}/{method_name}`

**Example:**
```
php://examples/XHEExcel/open
```

**Result:**
- Code examples for using the method

### Category Resource
**URI:** `php://category/{category}`

**Example:**
```
php://category/System
```

**Result:**
- List all resources in the category

## Lifecycle

1. Determine the required resource
2. Generate the URI
3. Call the resource using `resources_read`
4. Process the result

## Important Notes

- The URI must be valid
- XHEE classes are accessible via static properties
- All resources return JSON format

## Related Skills
- `xhe-static-property-access` - Accessing XHE static properties
- `xhe-server-side-architecture` - XHE API architecture