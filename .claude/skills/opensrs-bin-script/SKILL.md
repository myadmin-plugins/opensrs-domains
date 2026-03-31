---
name: opensrs-bin-script
description: Creates a new OpenSRS CLI script in bin/. Follows the project pattern: require autoload + openSRS_loader.php, json_encode func+attributes, process via opensrs\Request, print formatted output. Use when user says 'add bin script', 'new CLI command', 'add opensrs command', or creates files in bin/. Do NOT use for modifying existing bin scripts.
---
# OpenSRS Bin Script

## Critical

- Every script MUST go in a subdirectory under `bin/`. The 12 valid subdirectories are: `authentication`, `bulkchange`, `cookie`, `dnszone`, `forwarding`, `lookup`, `nameserver`, `personalnames`, `provisioning`, `subreseller`, `subuser`, `transfer`. Never create scripts directly in `bin/`.
- The `func` value in the JSON call string MUST match the filename without `.php`. Example: `bin/lookup/lookupDomain.php` uses `'func' => 'lookupDomain'`.
- File naming convention is **camelCase** with a category prefix matching the subdirectory's short name:
  - `lookup/` → `lookup*.php` (e.g., `lookupDomain.php`, `lookupGetDomain.php`)
  - `provisioning/` → `prov*.php` (e.g., `provActivate.php`, `provRenew.php`)
  - `transfer/` → `trans*.php` (e.g., `transCheck.php`, `transProcess.php`)
  - `nameserver/` → `ns*.php` (e.g., `nsCreate.php`, `nsDelete.php`)
  - `dnszone/` → `dns*.php` (e.g., `dnsCreate.php`, `dnsGet.php`)
  - `authentication/` → `auth*.php` (e.g., `authChangePassword.php`)
  - `cookie/` → `cookie*.php` (e.g., `cookieSet.php`, `cookieDelete.php`)
  - `forwarding/` → `fwd*.php` (e.g., `fwdCreate.php`, `fwdGet.php`)
  - `subreseller/` → `subres*.php` (e.g., `subresCreate.php`, `subresGet.php`)
  - `subuser/` → `subuser*.php` (e.g., `subuserAdd.php`, `subuserGet.php`)
  - `personalnames/` → `pers*.php` (e.g., `persQuery.php`)
  - `bulkchange/` → `bulk*.php` (e.g., `bulkChange.php`)
- The `require_once` paths use `__DIR__` with exactly `'/../../../../autoload.php'` and `'/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php'`. The depth is always 4 levels up (`../../..../../`) because scripts are at `bin/<subdir>/<script>.php`.

## Instructions

1. **Determine the correct subdirectory and filename.** Identify which OpenSRS API domain the command belongs to. Match it to one of the 12 subdirectories listed above. Name the file using the subdirectory's camelCase prefix + the action name. Verify the subdirectory exists under `bin/` before proceeding.

2. **Create the script file.** Use this exact template:

```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php';
use opensrs\Request;

$callstring = json_encode(
    [
        'func' => '<funcName>', 'attributes' => [<attributes>]
    ]
);
try {
    $request = new Request();
    $osrsHandler = $request->process('json', $callstring);

    print('In: ' . $callstring . "\n");
    print('Out: ' . json_encode(json_decode($osrsHandler->resultFormatted), JSON_PRETTY_PRINT) . "\n");
} catch (\opensrs\Exception $e) {
    var_dump($e->getMessage());
}
```

Replace `<funcName>` with the filename without `.php`. Replace `<attributes>` with the required API parameters. Verify the `func` value matches the filename exactly.

3. **Add CLI argument handling if needed.** When the script requires user input (like a domain name), read from `$_SERVER['argv']`. Follow the existing pattern:
   - Required args: `$_SERVER['argv'][1]` (no default)
   - Optional args with defaults: `$_SERVER['argv'][2] ?? 'default_value'`
   - Never use `$argc`/`$argv` globals — always use `$_SERVER['argv']`

4. **Choose the correct output format.** Most scripts use `$osrsHandler->resultFormatted` with JSON pretty-print. Some lookup scripts additionally print `$osrsHandler->resultFullRaw` with `print_r()` for debugging. Use only `resultFormatted` unless the raw XML response structure is specifically needed.

5. **Verify the file.** Run `php -l bin/<subdir>/<filename>.php` to check for syntax errors. Verify the shebang line is `#!/usr/bin/env php` on line 1.

## Examples

### Example 1: Simple script with no arguments

User says: "Add a bin script for DNS zone reset"

Actions:
- Subdirectory: `dnszone/` (DNS zone operations)
- Filename: `dnsReset.php` (prefix `dns` + action `Reset`)
- Func: `dnsReset`
- No CLI arguments needed

Result — `bin/dnszone/dnsReset.php`:
```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php';
use opensrs\Request;

$callstring = json_encode(
    [
        'func' => 'dnsReset', 'attributes' => []
    ]
);
try {
    $request = new Request();
    $osrsHandler = $request->process('json', $callstring);

    print('In: ' . $callstring . "\n");
    print('Out: ' . json_encode(json_decode($osrsHandler->resultFormatted), JSON_PRETTY_PRINT) . "\n");
} catch (\opensrs\Exception $e) {
    var_dump($e->getMessage());
}
```

### Example 2: Script with CLI arguments

User says: "Create a lookup script to check domain price"

Actions:
- Subdirectory: `lookup/`
- Filename: `lookupGetPrice.php` (prefix `lookup` + action `GetPrice`)
- Func: `lookupGetPrice`
- Needs domain as required arg, period as optional

Result — `bin/lookup/lookupGetPrice.php`:
```php
#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php';
use opensrs\Request;

$callstring = json_encode(
    [
        'func' => 'lookupGetPrice', 'attributes' => [
        'domain' => $_SERVER['argv'][1],
        'period' => $_SERVER['argv'][2] ?? '1'
    ]
    ]
);
try {
    $request = new Request();
    $osrsHandler = $request->process('json', $callstring);

    print('In: ' . $callstring . "\n");
    print('Out: ' . json_encode(json_decode($osrsHandler->resultFormatted), JSON_PRETTY_PRINT) . "\n");
} catch (\opensrs\Exception $e) {
    var_dump($e->getMessage());
}
```

## Common Issues

- **`PHP Fatal error: Uncaught Error: Class 'opensrs\Request' not found`**: The autoload path is wrong. Verify the script is exactly 2 levels deep under `bin/` (i.e., `bin/<subdir>/<script>.php`) so that `__DIR__ . '/../../../../autoload.php'` resolves correctly to the vendor root's `autoload.php`.

- **`failed to open stream: No such file or directory` for `openSRS_loader.php`**: The second `require_once` path must be `'/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php'`. Do not shorten this to a relative path.

- **Script runs but returns empty output or null**: The `func` value does not match a registered OpenSRS handler. Ensure `func` matches the filename exactly (without `.php`) and corresponds to a valid `opensrs\` request class.

- **`Undefined offset: 1` error**: The script expects a CLI argument that wasn't provided. If the attribute is required, document the usage in a comment at the top or provide a clear error: check `isset($_SERVER['argv'][1])` before using it. For optional arguments, use the null coalescing operator: `$_SERVER['argv'][2] ?? 'default'`.

- **Wrong subdirectory**: If unsure which subdirectory a new command belongs in, check the OpenSRS API documentation category. The subdirectory names map directly to OpenSRS API action categories (lookup, provisioning, transfer, nameserver, dnszone, authentication, cookie, forwarding, subreseller, subuser, personalnames, bulkchange).