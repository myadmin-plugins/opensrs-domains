<?php

namespace Detain\MyAdminOpenSRS\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the global helper functions defined in openSRS_loader.php.
 *
 * Validates array2object, object2array, convertArray2Formatted,
 * convertFormatted2array, and array_filter_recursive.
 *
 * @covers ::array2object
 * @covers ::object2array
 * @covers ::convertArray2Formatted
 * @covers ::convertFormatted2array
 * @covers ::array_filter_recursive
 */
class LoaderFunctionsTest extends TestCase
{
    /**
     * Ensure the loader file has been included.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        if (!function_exists('array2object')) {
            require_once __DIR__ . '/../src/openSRS_loader.php';
        }
    }

    /**
     * Test array2object converts a flat array to an object.
     *
     * @return void
     */
    public function testArray2ObjectConvertsSimpleArray(): void
    {
        $data = ['name' => 'example', 'value' => 'test'];
        $result = array2object($data);

        $this->assertIsObject($result);
        $this->assertSame('example', $result->name);
        $this->assertSame('test', $result->value);
    }

    /**
     * Test array2object converts nested arrays recursively.
     *
     * @return void
     */
    public function testArray2ObjectConvertsNestedArray(): void
    {
        $data = ['outer' => ['inner' => 'value']];
        $result = array2object($data);

        $this->assertIsObject($result);
        $this->assertIsObject($result->outer);
        $this->assertSame('value', $result->outer->inner);
    }

    /**
     * Test array2object returns non-array input unchanged.
     *
     * @return void
     */
    public function testArray2ObjectReturnsNonArrayUnchanged(): void
    {
        $this->assertSame('string', array2object('string'));
        $this->assertSame(42, array2object(42));
        $this->assertNull(array2object(null));
    }

    /**
     * Test array2object lowercases and trims keys.
     *
     * @return void
     */
    public function testArray2ObjectLowercasesKeys(): void
    {
        $data = ['MyKey' => 'val', '  UPPER  ' => 'test'];
        $result = array2object($data);

        $this->assertSame('val', $result->mykey);
        $this->assertSame('test', $result->upper);
    }

    /**
     * Test array2object handles empty array.
     *
     * @return void
     */
    public function testArray2ObjectHandlesEmptyArray(): void
    {
        $result = array2object([]);
        $this->assertIsObject($result);
    }

    /**
     * Test object2array converts a simple object to an array.
     *
     * @return void
     */
    public function testObject2ArrayConvertsSimpleObject(): void
    {
        $obj = new \stdClass();
        $obj->name = 'example';
        $obj->value = 42;

        $result = object2array($obj);

        $this->assertIsArray($result);
        $this->assertSame('example', $result['name']);
        $this->assertSame(42, $result['value']);
    }

    /**
     * Test object2array converts nested objects recursively.
     *
     * @return void
     */
    public function testObject2ArrayConvertsNestedObject(): void
    {
        $inner = new \stdClass();
        $inner->key = 'val';
        $outer = new \stdClass();
        $outer->nested = $inner;

        $result = object2array($outer);

        $this->assertIsArray($result);
        $this->assertIsArray($result['nested']);
        $this->assertSame('val', $result['nested']['key']);
    }

    /**
     * Test object2array returns scalars unchanged.
     *
     * @return void
     */
    public function testObject2ArrayReturnsScalarsUnchanged(): void
    {
        $this->assertSame('hello', object2array('hello'));
        $this->assertSame(123, object2array(123));
        $this->assertNull(object2array(null));
    }

    /**
     * Test convertArray2Formatted converts array to JSON string.
     *
     * @return void
     */
    public function testConvertArray2FormattedJson(): void
    {
        $data = ['key' => 'value', 'num' => 1];
        $result = convertArray2Formatted('json', $data);

        $this->assertIsString($result);
        $decoded = json_decode($result, true);
        $this->assertSame('value', $decoded['key']);
        $this->assertSame(1, $decoded['num']);
    }

    /**
     * Test convertArray2Formatted returns empty string for unknown type.
     *
     * @return void
     */
    public function testConvertArray2FormattedUnknownType(): void
    {
        $result = convertArray2Formatted('xml', ['key' => 'value']);
        $this->assertSame('', $result);
    }

    /**
     * Test convertArray2Formatted returns empty string for empty arguments.
     *
     * @return void
     */
    public function testConvertArray2FormattedEmptyArgs(): void
    {
        $result = convertArray2Formatted();
        $this->assertSame('', $result);
    }

    /**
     * Test convertFormatted2array converts JSON string to array.
     *
     * @return void
     */
    public function testConvertFormatted2ArrayJson(): void
    {
        $json = '{"key":"value","num":1}';
        $result = convertFormatted2array('json', $json);

        $this->assertIsArray($result);
        $this->assertSame('value', $result['key']);
        $this->assertSame(1, $result['num']);
    }

    /**
     * Test convertFormatted2array returns empty string for unknown type.
     *
     * @return void
     */
    public function testConvertFormatted2ArrayUnknownType(): void
    {
        $result = convertFormatted2array('xml', '<data/>');
        $this->assertSame('', $result);
    }

    /**
     * Test array_filter_recursive removes empty values.
     *
     * @return void
     */
    public function testArrayFilterRecursiveRemovesEmptyValues(): void
    {
        $input = ['a' => 'value', 'b' => '', 'c' => 0, 'd' => null, 'e' => 'keep'];
        $result = array_filter_recursive($input);

        $this->assertArrayHasKey('a', $result);
        $this->assertArrayHasKey('e', $result);
        $this->assertArrayNotHasKey('b', $result);
        $this->assertArrayNotHasKey('c', $result);
        $this->assertArrayNotHasKey('d', $result);
    }

    /**
     * Test array_filter_recursive works on nested arrays.
     *
     * @return void
     */
    public function testArrayFilterRecursiveHandlesNestedArrays(): void
    {
        $input = [
            'level1' => [
                'keep' => 'yes',
                'remove' => '',
                'nested' => [
                    'a' => 'ok',
                    'b' => null,
                ],
            ],
            'empty_nested' => [
                'x' => '',
                'y' => 0,
            ],
        ];

        $result = array_filter_recursive($input);

        $this->assertArrayHasKey('level1', $result);
        $this->assertSame('yes', $result['level1']['keep']);
        $this->assertArrayNotHasKey('remove', $result['level1']);
        $this->assertSame('ok', $result['level1']['nested']['a']);
    }

    /**
     * Test array_filter_recursive returns empty array for all-empty input.
     *
     * @return void
     */
    public function testArrayFilterRecursiveAllEmpty(): void
    {
        $input = ['a' => '', 'b' => null, 'c' => 0];
        $result = array_filter_recursive($input);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test array_filter_recursive preserves non-empty values.
     *
     * @return void
     */
    public function testArrayFilterRecursivePreservesNonEmpty(): void
    {
        $input = ['a' => 'hello', 'b' => 42, 'c' => true, 'd' => [1, 2, 3]];
        $result = array_filter_recursive($input);

        $this->assertSame('hello', $result['a']);
        $this->assertSame(42, $result['b']);
        $this->assertTrue($result['c']);
    }
}
