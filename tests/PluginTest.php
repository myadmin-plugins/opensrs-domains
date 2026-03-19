<?php

namespace Detain\MyAdminOpenSRS\Tests;

use Detain\MyAdminOpenSRS\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Tests for the Plugin class.
 *
 * Validates class structure, static properties, hook registration,
 * and event handler method signatures without invoking external services.
 *
 * @covers \Detain\MyAdminOpenSRS\Plugin
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * Set up the reflection instance for the Plugin class.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Test that Plugin class can be instantiated.
     *
     * @return void
     */
    public function testPluginCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Test that the $name static property is set correctly.
     *
     * @return void
     */
    public function testNamePropertyIsCorrect(): void
    {
        $this->assertSame('OpenSRS Domains', Plugin::$name);
    }

    /**
     * Test that the $description static property is a non-empty string.
     *
     * @return void
     */
    public function testDescriptionPropertyIsNonEmpty(): void
    {
        $this->assertIsString(Plugin::$description);
        $this->assertNotEmpty(Plugin::$description);
    }

    /**
     * Test that the $help static property is a non-empty string.
     *
     * @return void
     */
    public function testHelpPropertyIsNonEmpty(): void
    {
        $this->assertIsString(Plugin::$help);
        $this->assertNotEmpty(Plugin::$help);
    }

    /**
     * Test that the $module static property is set to 'domains'.
     *
     * @return void
     */
    public function testModulePropertyIsDomains(): void
    {
        $this->assertSame('domains', Plugin::$module);
    }

    /**
     * Test that the $type static property is set to 'service'.
     *
     * @return void
     */
    public function testTypePropertyIsService(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Test that getHooks returns an array with the expected event keys.
     *
     * @return void
     */
    public function testGetHooksReturnsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();

        $this->assertIsArray($hooks);
        $this->assertArrayHasKey('domains.load_addons', $hooks);
        $this->assertArrayHasKey('domains.activate', $hooks);
        $this->assertArrayHasKey('domains.settings', $hooks);
        $this->assertArrayHasKey('function.requirements', $hooks);
    }

    /**
     * Test that getHooks returns exactly 4 hooks.
     *
     * @return void
     */
    public function testGetHooksReturnsCorrectCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(4, $hooks);
    }

    /**
     * Test that each hook value is a callable array with the class name.
     *
     * @return void
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();

        foreach ($hooks as $eventName => $handler) {
            $this->assertIsArray($handler, "Hook handler for {$eventName} should be an array");
            $this->assertCount(2, $handler, "Hook handler for {$eventName} should have 2 elements");
            $this->assertSame(Plugin::class, $handler[0], "Hook handler class for {$eventName} should be Plugin");
            $this->assertIsString($handler[1], "Hook handler method for {$eventName} should be a string");
        }
    }

    /**
     * Test that all hook handler methods actually exist on the Plugin class.
     *
     * @return void
     */
    public function testGetHooksMethodsExist(): void
    {
        $hooks = Plugin::getHooks();

        foreach ($hooks as $eventName => $handler) {
            $methodName = $handler[1];
            $this->assertTrue(
                $this->reflection->hasMethod($methodName),
                "Plugin class should have method '{$methodName}' referenced by hook '{$eventName}'"
            );
        }
    }

    /**
     * Test that the getAddon method exists and is public static.
     *
     * @return void
     */
    public function testGetAddonMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getAddon');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that the getActivate method exists and is public static.
     *
     * @return void
     */
    public function testGetActivateMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that the getSettings method exists and is public static.
     *
     * @return void
     */
    public function testGetSettingsMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that the getRequirements method exists and is public static.
     *
     * @return void
     */
    public function testGetRequirementsMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that the activate_domain method exists and is public static.
     *
     * @return void
     */
    public function testActivateDomainMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('activate_domain');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that the doAddonEnable method exists and is public static.
     *
     * @return void
     */
    public function testDoAddonEnableMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('doAddonEnable');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that the doAddonDisable method exists and is public static.
     *
     * @return void
     */
    public function testDoAddonDisableMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('doAddonDisable');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that getAddon accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetAddonAcceptsGenericEventParameter(): void
    {
        $method = $this->reflection->getMethod('getAddon');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
        $this->assertTrue($params[0]->hasType());
    }

    /**
     * Test that getActivate accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetActivateAcceptsGenericEventParameter(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
    }

    /**
     * Test that getSettings accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetSettingsAcceptsGenericEventParameter(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
    }

    /**
     * Test that getRequirements accepts a GenericEvent parameter.
     *
     * @return void
     */
    public function testGetRequirementsAcceptsGenericEventParameter(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());
    }

    /**
     * Test that the Plugin class has exactly the expected static properties.
     *
     * @return void
     */
    public function testPluginHasExpectedStaticProperties(): void
    {
        $properties = $this->reflection->getStaticProperties();

        $this->assertArrayHasKey('name', $properties);
        $this->assertArrayHasKey('description', $properties);
        $this->assertArrayHasKey('help', $properties);
        $this->assertArrayHasKey('module', $properties);
        $this->assertArrayHasKey('type', $properties);
    }

    /**
     * Test that the hooks use the module property in their event names.
     *
     * @return void
     */
    public function testHookEventNamesUseModuleProperty(): void
    {
        $hooks = Plugin::getHooks();
        $module = Plugin::$module;

        $this->assertArrayHasKey($module . '.load_addons', $hooks);
        $this->assertArrayHasKey($module . '.activate', $hooks);
        $this->assertArrayHasKey($module . '.settings', $hooks);
    }

    /**
     * Test that the constructor has no required parameters.
     *
     * @return void
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $requiredParams = array_filter($params, function ($p) {
            return !$p->isOptional();
        });

        $this->assertCount(0, $requiredParams);
    }

    /**
     * Test that activate_domain method accepts an integer id parameter.
     *
     * @return void
     */
    public function testActivateDomainAcceptsIdParameter(): void
    {
        $method = $this->reflection->getMethod('activate_domain');
        $params = $method->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
    }

    /**
     * Test that doAddonEnable has expected parameters.
     *
     * @return void
     */
    public function testDoAddonEnableHasExpectedParameters(): void
    {
        $method = $this->reflection->getMethod('doAddonEnable');
        $params = $method->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
    }

    /**
     * Test that doAddonDisable has expected parameters.
     *
     * @return void
     */
    public function testDoAddonDisableHasExpectedParameters(): void
    {
        $method = $this->reflection->getMethod('doAddonDisable');
        $params = $method->getParameters();

        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertSame('serviceOrder', $params[0]->getName());
        $this->assertSame('repeatInvoiceId', $params[1]->getName());
    }
}
