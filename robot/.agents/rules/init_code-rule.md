---
description: Init script rule
---

## Initialization Code Rule

### Overview
When generating a new PHP script, always include the following initialization block at the very beginning of the code to ensure access to the XHE API classes and methods.

### Required Initialization Code
```php
$xhe_host = getenv("RPABOT_HOST_URL");
// Local project init.php (shim → Studio Templates/init.php). Do not use ../../Templates here.
require_once(__DIR__ . "/init.php");
```

### Usage Guidelines
- Place this code at the very beginning of each PHP script
- This initialization is mandatory for all XHE API operations
- Use `__DIR__ . "/../../Templates/init.php"` only in Test Samples layout without a local `init.php`