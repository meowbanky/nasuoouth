<?php
/**
 * OOUTH Salary API Configuration
 * Store your API credentials here
 * 
 * SECURITY NOTE: This file should be added to .gitignore
 */

// API Configuration
define('OOUTH_API_BASE_URL', 'https://oouthsalary.com.ng/api/v1');
define('OOUTH_API_KEY', 'oouth_006_deduc_62_0ac9adef84f15ff2'); // Replace with your actual API key
define('OOUTH_API_SECRET', '288ecf04fcf97501a846efb1e384f604d5b2c249357cd49a972abdfcc5beb048'); // Replace with your actual API secret
define('OOUTH_ORGANIZATION_ID', '006'); // Your organization ID

// Resource Configuration (what your API key has access to)
define('OOUTH_RESOURCE_TYPE', 'deduction'); // 'deduction' or 'allowance'
define('OOUTH_RESOURCE_ID', '62'); // The ID of the deduction/allowance
define('OOUTH_RESOURCE_NAME', 'OOUTH NASU'); // Name for display

// API Settings
define('OOUTH_API_TIMEOUT', 30); // Request timeout in seconds
define('OOUTH_API_RETRY_ATTEMPTS', 3); // Number of retry attempts on failure

// Webhook Configuration (optional)
define('OOUTH_WEBHOOK_SECRET', ''); // Your webhook secret if registered

// Debug Mode
define('OOUTH_API_DEBUG', true); // Set to false in production