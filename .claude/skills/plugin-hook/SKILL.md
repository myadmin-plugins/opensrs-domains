---
name: plugin-hook
description: Adds a new Symfony EventDispatcher hook to src/Plugin.php following the getHooks() pattern: register event name → [Plugin::class, 'methodName'], implement as public static method accepting GenericEvent. Use when user says 'add hook', 'new event handler', 'plugin event', 'register hook', 'listen to event'. Do NOT use for OpenSRS API wrapper methods in OpenSRS.php, CLI bin/ scripts, or test files.
---
# Plugin Hook

Add a new Symfony EventDispatcher hook to `src/Plugin.php` — register the event in `getHooks()` and implement the corresponding `public static` handler method.

## Critical

- **Every hook handler referenced in `getHooks()` MUST exist as a `public static` method on the `Plugin` class.** The test suite (`tests/PluginTest.php`) validates this via reflection — a missing method will fail `testGetHooksMethodsExist`.
- **Event handlers that respond to module-scoped events MUST use `self::$module` prefix** (e.g., `self::$module.'.my_event'`), not a hardcoded string like `'domains.my_event'`.
- **Never modify `OpenSRS.php`** when adding hooks — hooks belong exclusively in `Plugin.php`.
- **Update the test** in `tests/PluginTest.php` — the `testGetHooksReturnsCorrectCount` test asserts an exact count of hooks. Increment it.

## Instructions

### Step 1: Read the current `getHooks()` method

Open `src/Plugin.php` and locate the `getHooks()` method (around line 31). Note the current hook count and the last entry in the return array.

**Verify:** You can see the full return array and know the current hook count before proceeding.

### Step 2: Choose the event name and method name

Follow the naming conventions from existing hooks:

| Event pattern | Method name pattern | When to use |
|---|---|---|
| `self::$module.'.event_name'` | `getEventName` or `handleEventName` | Module-scoped events (most hooks) |
| `'function.requirements'` | `getRequirements` | Global cross-module events |

Existing examples:
- `self::$module.'.load_addons'` → `getAddon`
- `self::$module.'.activate'` → `getActivate`
- `self::$module.'.settings'` → `getSettings`
- `'function.requirements'` → `getRequirements`

Method names use camelCase starting with `get` or a verb describing the action.

**Verify:** The event name does not collide with an existing key in `getHooks()`.

### Step 3: Register the hook in `getHooks()`

Add a new entry to the return array in `getHooks()`. Place it after the last existing entry, adding a trailing comma to the previous line if needed.

```php
public static function getHooks()
{
    return [
        self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
        self::$module.'.activate' => [__CLASS__, 'getActivate'],
        self::$module.'.settings' => [__CLASS__, 'getSettings'],
        'function.requirements' => [__CLASS__, 'getRequirements'],
        self::$module.'.your_event' => [__CLASS__, 'yourMethodName'],  // NEW
    ];
}
```

Key rules:
- Use `__CLASS__` (not `Plugin::class` or a string) as the first array element.
- Use `self::$module` for the event prefix (not `'domains'`).
- The method name string must exactly match the static method you create in Step 4.

**Verify:** The array is valid PHP (commas, brackets). Count the entries — this number is needed in Step 5.

### Step 4: Implement the handler method

Add the handler as a `public static` method on the `Plugin` class. Follow the exact pattern used by existing handlers.

**Template for a GenericEvent handler:**

```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function yourMethodName(GenericEvent $event)
{
    /**
     * @var \ExpectedSubjectType $subject
     */
    $subject = $event->getSubject();
    $settings = get_module_settings(self::$module);
    // Your logic here
}
```

Pattern details extracted from existing handlers:

1. **PHPDoc block** — always include `@param \Symfony\Component\EventDispatcher\GenericEvent $event`
2. **Type-hint the parameter** — `GenericEvent $event` (the `use` import already exists at the top of the file)
3. **Extract subject** — `$event->getSubject()` returns the dispatched object. Add a `@var` annotation for the expected type.
4. **Access event data** — use array syntax: `$event['key']` to read, `$event['key'] = $value` to write back.
5. **Stop propagation** — call `$event->stopPropagation()` if this handler exclusively owns the event (like `getActivate` does when `$event['category']` matches).
6. **Module settings** — call `$settings = get_module_settings(self::$module)` when you need the prefix, table name, etc.
7. **Logging** — use `myadmin_log(self::$module, 'info', 'message', __LINE__, __FILE__, self::$module, $id)` or `myadmin_log('opensrs', 'info', ...)` for OpenSRS-specific logs.

**Common subject types by event:**
- `.load_addons` → `\ServiceHandler` (the service being rendered)
- `.activate` → service ORM class (has `->getId()`, `->getCustid()`, etc.)
- `.settings` → `\MyAdmin\Settings` (call `$settings->add_text_setting(...)`, etc.)
- `function.requirements` → `\MyAdmin\Plugins\Loader` (call `$loader->add_requirement(...)`)

Place the new method near related handlers. For example, if adding a `.deactivate` hook, place it after `getActivate`.

**Verify:** The method name exactly matches the string in `getHooks()`. The method is `public static`. It accepts `GenericEvent $event` (or the appropriate type for non-event hooks).

### Step 5: Update the test hook count

Open `tests/PluginTest.php` and find `testGetHooksReturnsCorrectCount` (around line 118). Update the count:

```php
public function testGetHooksReturnsCorrectCount(): void
{
    $hooks = Plugin::getHooks();
    $this->assertCount(5, $hooks);  // was 4, now 5
}
```

Also update `testGetHooksReturnsExpectedKeys` to assert the new key exists:

```php
$this->assertArrayHasKey('domains.your_event', $hooks);
```

Note: In the test, use the literal string `'domains.your_event'` (matching `Plugin::$module` value), not `self::$module`.

**Verify:** Run `composer test` (or `vendor/bin/phpunit --filter PluginTest`) and confirm all tests pass.

### Step 6: Run the full test suite

```bash
composer test
```

**Verify:** All tests pass with 0 failures, 0 errors.

## Examples

### Example: Add a `domains.deactivate` hook

**User says:** "Add a hook for domain deactivation"

**Actions taken:**

1. Read `src/Plugin.php` and see 4 hooks in `getHooks()`.

2. Add to `getHooks()` return array:
```php
self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
```

3. Add handler method after `getActivate` (around line 105):
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getDeactivate(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('OPENSRS')) {
        myadmin_log(self::$module, 'info', 'OpenSRS Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $event->stopPropagation();
    }
}
```

4. Update `tests/PluginTest.php`:
   - `testGetHooksReturnsCorrectCount`: change `assertCount(4, ...)` → `assertCount(5, ...)`
   - `testGetHooksReturnsExpectedKeys`: add `$this->assertArrayHasKey('domains.deactivate', $hooks);`

5. Run `composer test` — all tests pass.

**Result:** New hook registered, handler implemented, tests updated and passing.

### Example: Add a `domains.dns_update` hook

**User says:** "Add a hook that fires when DNS records are updated"

**Actions taken:**

1. Add to `getHooks()`:
```php
self::$module.'.dns_update' => [__CLASS__, 'getDnsUpdate'],
```

2. Add handler:
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getDnsUpdate(GenericEvent $event)
{
    $serviceClass = $event->getSubject();
    if ($event['category'] == get_service_define('OPENSRS')) {
        $settings = get_module_settings(self::$module);
        $serviceInfo = $serviceClass->getServiceInfo();
        $hostname = $serviceInfo[$settings['PREFIX'] . '_hostname'];
        myadmin_log(self::$module, 'info', "OpenSRS DNS Update for {$hostname}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
        // Perform DNS update via OpenSRS API
        $event->stopPropagation();
    }
}
```

3. Update tests (count 4→5, add key assertion).

## Common Issues

**Error: `testGetHooksReturnsCorrectCount` fails with "Failed asserting that 5 matches expected 4"**
- You added the hook to `getHooks()` but forgot to update the count in `tests/PluginTest.php` line ~119. Change `assertCount(4, $hooks)` to match the new count.

**Error: `testGetHooksMethodsExist` fails with "Plugin class should have method 'yourMethod'"**
- The method name string in `getHooks()` does not match the actual method name. Check for typos — the string is case-sensitive.

**Error: `testGetHooksValuesAreCallableArrays` fails**
- You used `Plugin::class` or a string class name instead of `__CLASS__` as the first element. The test checks `$handler[0] === Plugin::class` which works with `__CLASS__` (they resolve to the same FQCN), but double-check the array structure is `[__CLASS__, 'methodName']`.

**Error: `PHP Fatal error: Call to undefined function get_module_settings()`**
- This happens when running the handler outside the MyAdmin environment. The handler methods depend on MyAdmin framework functions. Unit tests use reflection to verify structure, not execution. If you need to test handler logic, use integration tests (`composer test:integration` from the main MyAdmin project).

**Error: `GenericEvent` class not found**
- The `use Symfony\Component\EventDispatcher\GenericEvent;` import already exists at line 6 of `Plugin.php`. Do not add a duplicate. If somehow missing, add it after the namespace declaration.

**Handler never fires at runtime**
- Verify the event name matches exactly what the dispatcher uses. Check `include/config/hooks.json` or search for `run_event('your_event_name', ...)` in the main MyAdmin codebase to confirm the event is dispatched.
- Ensure the plugin is listed in `include/config/plugins.json` (it should already be for `detain/myadmin-opensrs-domains`).