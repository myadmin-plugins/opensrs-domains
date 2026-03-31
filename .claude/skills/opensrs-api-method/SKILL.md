---
name: opensrs-api-method
description: Adds a new static method to src/OpenSRS.php following existing patterns: StatisticClient::tick(), opensrs\Request::process(), request_log(), error handling with myadmin_log(). Use when user says 'add API method', 'new OpenSRS function', 'add domain operation', 'wrap OpenSRS call'. Do NOT use for Plugin.php hooks, test files, or bin/ CLI scripts.
---
# OpenSRS API Method

## Critical

- **All new methods go in `src/OpenSRS.php`** inside the `OpenSRS` class — never create separate files for API wrappers.
- **All API methods must be `public static`** — this is the universal pattern in OpenSRS.php. No instance methods except `loadDomainInfo()` and `__construct()`.
- **Two request paths exist** — pick the right one:
  - `self::request($callstring)` — JSON-based, uses `opensrs\Request::process()`. Used by most methods (`lookupGetDomain`, `lookupDomain`, `transferCheck`, `getCookieRaw`, `getNameserversRaw`, etc.).
  - `self::xmlRequest($action, $object, $options, $extra)` — Raw XML socket request. Used by `lock()`, `whoisPrivacy()`, `listDomainsByExpireyDate()`, `redeemDomain()`, `ackEvent()`, `pollEvent()`.
- **Never use PDO or raw SQL** — these methods interact with OpenSRS API only.
- **Never hardcode credentials** — use `OPENSRS_USERNAME` / `OPENSRS_KEY` constants (already loaded via `openSRS_config.php`).

## Instructions

### Step 1: Identify the OpenSRS API function name

Determine the OpenSRS API action your method will call. Check `bin/` subdirectories for existing script examples that call the same API function — they show the exact `func` name and required `attributes`.

```bash
# Example: find how provisioning renewal is called
cat bin/provisioning/provRenew.php
```

Verify the `func` name matches an existing OpenSRS API action before proceeding.

### Step 2: Choose the request method

**Use `self::request($callstring)` (JSON path)** when:
- The operation maps to a named `func` like `lookupGetDomain`, `cookieSet`, `nsGet`, `nsCreate`, `nsDelete`, `transCheck`, `lookupDomain`, `lookupGetPrice`, `SuggestDomain`
- You need the `$osrsHandler->resultFullRaw` response object

**Use `self::xmlRequest($action, $object, $options)` (XML path)** when:
- The operation uses raw action/object pairs like `modify`/`domain`, `REDEEM`/`DOMAIN`, `EVENT`/`POLL`, `EVENT`/`ACK`, `get_domains_by_expiredate`/`domain`
- You need to parse XML response arrays directly

Verify your choice matches how similar operations are handled in existing methods.

### Step 3: Write the method using the appropriate pattern

**Pattern A: JSON request path (most common)**

```php
/**
 * [Description of what this method does]. Can be called statically.
 *
 * @param string $domain the domain name
 * @param [type] $[param] [description]
 * @return array|bool
 */
public static function yourMethodName($domain, $otherParam = 'default')
{
    $callstring = [
        'func' => 'opensrsApiFuncName',
        'attributes' => [
            'domain' => $domain,
            'other_param' => $otherParam
    ]];
    $osrsHandler = self::request($callstring);
    request_log('domains', false, __FUNCTION__, 'opensrs', 'opensrsApiFuncName', $callstring, $osrsHandler);
    if ($osrsHandler === false) {
        return false;
    }
    return $osrsHandler->resultFullRaw;
}
```

**Pattern B: XML request path**

```php
/**
 * [Description of what this method does]. Can be called statically.
 *
 * @param string $domain the domain name
 * @return array|bool
 */
public static function yourMethodName($domain)
{
    $response = self::xmlRequest('action_name', 'object_type', ['domain_name' => $domain, 'data' => 'value']);
    if ($response === false) {
        return false;
    }
    $resultArray = $response['xml_array']['body']['data_block']['dt_assoc'];
    myadmin_log('domains', 'info', "OpenSRS::yourMethodName({$domain}) returned ".json_encode($resultArray), __LINE__, __FILE__);
    return $resultArray;
}
```

Verify the method signature uses `public static function` before proceeding.

### Step 4: Add the method to the OpenSRS class

Place the new method in `src/OpenSRS.php` inside the `OpenSRS` class, grouped logically near related methods:
- Lookup/query methods: near `lookupGetDomain()`, `lookupDomain()`, `checkDomainAvailable()`, `lookupDomainPrice()` (around line 565-673)
- Nameserver methods: near `getNameserversRaw()`, `createNameserverRaw()`, `deleteNameserverRaw()` (around line 430-515)
- Transfer methods: near `transferCheck()` (around line 536-550)
- Domain state methods: near `lock()`, `whoisPrivacy()` (around line 760-787)
- Event methods: near `ackEvent()`, `pollEvent()` (around line 871-908)
- Cookie/auth methods: near `getCookieRaw()` (around line 406-423)

Verify the method is inside the class closing brace (last `}` in the file).

### Step 5: Validate the implementation

1. Check that `self::request()` or `self::xmlRequest()` is used (not `new Request()` directly — that's only in `openSRS_loader.php`)
2. Check that error logging uses `myadmin_log('domains', ...)` or `myadmin_log('opensrs', ...)` with `__LINE__, __FILE__`
3. Check that `request_log()` is called after `self::request()` for JSON-path methods that perform mutations or are user-facing (see `getCookieRaw`, `getNameserversRaw`, `deleteNameserverRaw` for examples)
4. Check that `false` is returned on failure, not exceptions or null
5. Run `vendor/bin/phpunit` to ensure no syntax errors or test regressions

## Examples

### Example: Add a method to get domain auth info code

User says: "Add a method to get the auth info code for a domain"

Actions:
1. Check `bin/provisioning/` or `bin/lookup/` for an existing script that calls the relevant API function
2. Identify this uses `lookupGetDomain` with `type` = `domain_auth_info` (visible in the `lookupGetDomain` docblock)
3. Since this wraps an existing function with a specific type, add a convenience method:

```php
/**
 * Gets the domain authorization/transfer code for a domain. Can be called statically.
 *
 * @param string $domain the domain name
 * @return string|bool the auth info code or false on error
 */
public static function getDomainAuthInfo($domain)
{
    $result = self::lookupGetDomain($domain, 'domain_auth_info');
    if ($result === false) {
        return false;
    }
    return $result['attributes']['domain_auth_info'] ?? false;
}
```

4. Place near `lookupGetDomain()` in the file
5. Run `vendor/bin/phpunit` — all tests pass

### Example: Add a method to cancel a domain transfer

User says: "Add a cancelTransfer method"

Actions:
1. Check `bin/transfer/transCancel.php` for the func name and attributes
2. Identify it uses `func` => `transCancel` with `domain` and `order_id` attributes
3. Write the method:

```php
/**
 * Cancels a pending domain transfer. Can be called statically.
 *
 * @param string $domain the domain name
 * @param int $orderId the transfer order ID
 * @return array|bool
 */
public static function cancelTransfer($domain, $orderId)
{
    $callstring = [
        'func' => 'transCancel',
        'attributes' => [
            'domain' => $domain,
            'order_id' => $orderId
    ]];
    $osrsHandler = self::request($callstring);
    request_log('domains', false, __FUNCTION__, 'opensrs', 'transCancel', $callstring, $osrsHandler);
    if ($osrsHandler === false) {
        return false;
    }
    return $osrsHandler->resultFullRaw;
}
```

4. Place near `transferCheck()` method
5. Run `vendor/bin/phpunit`

## Common Issues

### `Call to undefined function request_log()`
`request_log()` is defined in the main MyAdmin codebase, not in this package. It's available at runtime but not during isolated unit tests. If tests fail on this:
1. Check that `tests/bootstrap.php` defines a stub for `request_log` if needed
2. The existing codebase uses `request_log()` freely in `getCookieRaw`, `getNameserversRaw`, `deleteNameserverRaw` — follow the same pattern

### `Class 'StatisticClient' not found`
This is expected. The `self::request()` method already guards with `class_exists(\StatisticClient::class, false)`. Never add `StatisticClient` calls in your new methods — they are handled centrally in `request()`.

### `Call to undefined function myadmin_log()`
This function is part of the main MyAdmin framework. It's available at runtime. In tests, it should be stubbed in `tests/bootstrap.php`. Check that file if you get this error during testing.

### Method returns `null` instead of `false` on error
Always check `if ($osrsHandler === false)` (strict comparison with `===`) immediately after `self::request()`. The `request()` method returns `false` on both `APIException` and `Exception`. Never let execution continue past a failed request.

### XML response path is wrong
When using `self::xmlRequest()`, the response structure varies. Common paths:
- `$response['xml_array']['body']['data_block']['dt_assoc']` — standard response
- `$response['xml_array']['OPS_envelope']['body']['data_block']['dt_assoc']['item']` — when parsing with `response_to_array()` (see `ackEvent`, `pollEvent`)
- `$response['lines'][N]` — raw line access (used in `lock()`, `whoisPrivacy()` — fragile, avoid if possible)

Check an existing method that uses the same `xmlRequest` action/object pair to determine the correct path.

### `$callstring` array format is wrong
The `func` key must be at the top level, and all API parameters go inside the `attributes` key:
```php
// CORRECT
$callstring = [
    'func' => 'funcName',
    'attributes' => [
        'domain' => $domain
]];

// WRONG — missing attributes wrapper
$callstring = [
    'func' => 'funcName',
    'domain' => $domain
];
```