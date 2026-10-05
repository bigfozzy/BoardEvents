---
description: Quit rule
---

## Quit Rule

### Overview
When generating a new PHP script, always include following block at very end of the code to quit server process the XHE API framework.

### Required Quit Code
```php
// Quit the application
WINDOW::$app->quit();
```

### Usage Guidelines
- Include this code to stop all processes at server side