---
name: phpunit-test
description: Creates or extends PHPUnit 9 tests in tests/ following project patterns: ReflectionClass for structure tests, stub functions from tests/bootstrap.php, namespace Detain\MyAdminOpenSRS\Tests. Use when user says 'add test', 'write tests', 'test coverage'. Do NOT use for non-test PHP files.
---
# PHPUnit Test Creation

## Critical

- **Namespace**: All test classes MUST use `namespace Detain\MyAdminOpenSRS\Tests;`
- **Bootstrap**: Tests rely on `tests/bootstrap.php` which defines stub functions (`myadmin_log`, `get_module_settings`, `get_service`, `run_event`, `get_module_db`, etc.) and constants (`OPENSRS_USERNAME`, `OPENSRS_KEY`, `DOMAIN`, etc.). Never redefine these stubs in test files.
- **No external API calls**: Tests must NEVER call real OpenSRS APIs. Use `ReflectionClass` for structure validation and test only pure methods (no network I/O).
- **PHPUnit version**: 9.x — use `PHPUnit\Framework\TestCase`, void return types on test methods, `setUp(): void`.
- **Config**: `phpunit.xml.dist` at project root bootstraps `tests/bootstrap.php` and scans the `tests/` directory.

## Instructions

### Step 1: Determine test scope

Identify which class or functions to test:
- `src/Plugin.php` → `tests/PluginTest.php` (class structure, hooks, static properties)
- `src/OpenSRS.php` → `tests/OpenSRSTest.php` (method signatures, pure methods like `response_to_array`, `getEventTypes`)
- `src/openSRS_loader.php` → `tests/LoaderFunctionsTest.php` (global helper functions)

Verify the source file exists before writing tests: `ls src/ClassName.php`

### Step 2: Create or edit the test file

File naming: `tests/{ClassName}Test.php` — PascalCase matching the source class name.

Required boilerplate:

```php
<?php

namespace Detain\MyAdminOpenSRS\Tests;

use Detain\MyAdminOpenSRS\ClassName;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the ClassName class.
 *
 * @covers \Detain\MyAdminOpenSRS\ClassName
 */
class ClassNameTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * Set up the reflection instance.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(ClassName::class);
    }
}
```

For global function tests (no class), omit `ReflectionClass` and use `setUpBeforeClass` to require the source file:

```php
public static function setUpBeforeClass(): void
{
    if (!function_exists('functionName')) {
        require_once __DIR__ . '/../src/sourceFile.php';
    }
}
```

Verify the file compiles: `php -l tests/ClassNameTest.php`

### Step 3: Write structure tests using ReflectionClass

This project validates class contracts via reflection — NOT by calling methods that hit external services.

**Test that a class can be instantiated:**
```php
public function testClassCanBeInstantiated(): void
{
    $instance = new ClassName();
    $this->assertInstanceOf(ClassName::class, $instance);
}
```

**Test static properties exist and have correct values:**
```php
public function testNamePropertyIsCorrect(): void
{
    $this->assertSame('Expected Value', ClassName::$name);
}

public function testHasExpectedStaticProperties(): void
{
    $properties = $this->reflection->getStaticProperties();
    $this->assertArrayHasKey('name', $properties);
    $this->assertArrayHasKey('module', $properties);
}
```

**Test method exists and is public/static:**
```php
public function testMethodIsPublicStatic(): void
{
    $method = $this->reflection->getMethod('methodName');
    $this->assertTrue($method->isPublic());
    $this->assertTrue($method->isStatic());
}
```

**Test method signature (parameter names, defaults, count):**
```php
public function testMethodSignature(): void
{
    $method = $this->reflection->getMethod('methodName');
    $params = $method->getParameters();

    $this->assertCount(2, $params);
    $this->assertSame('domain', $params[0]->getName());
    $this->assertSame('type', $params[1]->getName());
    $this->assertTrue($params[1]->isOptional());
    $this->assertSame('default_val', $params[1]->getDefaultValue());
}
```

**Test instance without constructor (for classes that call APIs in constructor):**
```php
public function testPropertyDefaults(): void
{
    $instance = $this->reflection->newInstanceWithoutConstructor();
    $this->assertSame('domains', $instance->module);
}
```

**Test public properties exist:**
```php
public function testExpectedPropertiesExist(): void
{
    $expected = ['id', 'cookie', 'module'];
    foreach ($expected as $prop) {
        $this->assertTrue(
            $this->reflection->hasProperty($prop),
            "Should have property '{$prop}'"
        );
        $this->assertTrue(
            $this->reflection->getProperty($prop)->isPublic(),
            "Property '{$prop}' should be public"
        );
    }
}
```

Verify each test method name starts with `test` and has `: void` return type.

### Step 4: Write pure-method tests

Only test methods that have no side effects (no API calls, no DB, no file I/O).

```php
public function testResponseToArraySimpleValue(): void
{
    $input = [
        ['attr' => ['key' => 'name'], 'value' => 'example.com'],
    ];
    $result = OpenSRS::response_to_array($input);
    $this->assertSame('example.com', $result['name']);
}

public function testResponseToArrayEmptyInput(): void
{
    $result = OpenSRS::response_to_array([]);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
}
```

Always test: empty input, typical input, nested/complex input, edge cases.

### Step 5: Write hook/event tests (for Plugin classes)

```php
public function testGetHooksReturnsExpectedKeys(): void
{
    $hooks = Plugin::getHooks();
    $this->assertIsArray($hooks);
    $this->assertArrayHasKey('domains.load_addons', $hooks);
}

public function testGetHooksMethodsExist(): void
{
    $hooks = Plugin::getHooks();
    foreach ($hooks as $eventName => $handler) {
        $this->assertTrue(
            $this->reflection->hasMethod($handler[1]),
            "Plugin should have method '{$handler[1]}' for hook '{$eventName}'"
        );
    }
}

public function testHookHandlersAreCallableArrays(): void
{
    $hooks = Plugin::getHooks();
    foreach ($hooks as $eventName => $handler) {
        $this->assertIsArray($handler);
        $this->assertCount(2, $handler);
        $this->assertSame(Plugin::class, $handler[0]);
        $this->assertIsString($handler[1]);
    }
}
```

### Step 6: Run tests and verify

```bash
cd /home/sites/mystage/vendor/detain/myadmin-opensrs-domains
vendor/bin/phpunit --filter ClassNameTest
```

All tests must pass. If a test fails because a method/property doesn't exist, that's a valid finding — the test is correct and reveals a missing contract.

Verify with full suite: `composer test`

## Examples

**User says:** "Add tests for OpenSRS"

**Actions taken:**
1. Read `src/OpenSRS.php` to identify all public methods and properties
2. Check if `tests/OpenSRSTest.php` exists — it does, so extend it
3. For each untested method, add a signature test using `$this->reflection->getMethod()`
4. For pure methods (no API calls), add input/output tests
5. Run `vendor/bin/phpunit --filter OpenSRSTest`

**Result:** New test methods added to `tests/OpenSRSTest.php` covering method signatures, parameter defaults, and pure function behavior.

---

**User says:** "Write tests for the loader functions"

**Actions taken:**
1. Read `src/openSRS_loader.php` to identify exported functions
2. Open `tests/LoaderFunctionsTest.php`
3. Add tests for each function: empty input, typical input, nested structures
4. Use `setUpBeforeClass` to require the source file
5. Run `vendor/bin/phpunit --filter LoaderFunctionsTest`

**Result:** Tests validate `array2object`, `object2array`, `convertArray2Formatted`, `convertFormatted2array`, `array_filter_recursive` with various inputs.

## Common Issues

**Error: `Class 'StatisticClient' not found`**
- The bootstrap at `tests/bootstrap.php` handles this with a stub. If you see this error, ensure `phpunit.xml.dist` has `bootstrap="tests/bootstrap.php"`. Do NOT add a separate require in your test file.

**Error: `Call to undefined function myadmin_log()` (or any global function)**
- The function stub is missing from `tests/bootstrap.php`. Add a stub following the existing pattern:
```php
if (!function_exists('function_name')) {
    function function_name($param1, $param2) {
        // no-op or return sensible default
    }
}
```

**Error: `Class 'Detain\MyAdminOpenSRS\Tests\ClassNameTest' not found`**
- Verify `composer.json` has the `autoload-dev` PSR-4 mapping: `"Detain\\MyAdminOpenSRS\\Tests\\": "tests/"`
- Run `composer dump-autoload` to regenerate the autoloader.

**Error: `ReflectionException: Class Detain\MyAdminOpenSRS\ClassName does not exist`**
- The source file failed to load. Check for syntax errors: `php -l src/ClassName.php`
- Check for missing constants — add them to `tests/bootstrap.php` using the `if (!defined())` pattern.

**Error: `This test did not perform any assertions` (risky test)**
- `phpunit.xml.dist` has `beStrictAboutTestsThatDoNotTestAnything="true"`. Every test method MUST contain at least one assertion.

**Error: `Test code or tested code did not (only) close its own output buffers`**
- `phpunit.xml.dist` has `beStrictAboutOutputDuringTests="true"`. If the code under test produces output, either test a pure method instead or use `$this->expectOutputString()` / `$this->expectOutputRegex()`.

**Tests pass locally but `composer test` fails:**
- `composer test` runs `phpunit` from `vendor/bin/`. Ensure `require-dev` includes `"phpunit/phpunit": "^9.6"` and run `composer install` first.