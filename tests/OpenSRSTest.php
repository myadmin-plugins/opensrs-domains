<?php

namespace Detain\MyAdminOpenSRS\Tests;

use Detain\MyAdminOpenSRS\OpenSRS;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for the OpenSRS class.
 *
 * Validates class structure, pure methods (response_to_array, getEventTypes),
 * method signatures, and properties without calling external APIs.
 *
 * @covers \Detain\MyAdminOpenSRS\OpenSRS
 */
class OpenSRSTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * Set up the reflection instance for the OpenSRS class.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(OpenSRS::class);
    }

    /**
     * Test that OpenSRS class exists and is instantiable via reflection.
     *
     * @return void
     */
    public function testOpenSRSClassExists(): void
    {
        $this->assertTrue(class_exists(OpenSRS::class));
    }

    /**
     * Test that the constructor can be called with no arguments (returns early).
     *
     * @return void
     */
    public function testConstructorWithNoArguments(): void
    {
        $instance = $this->reflection->newInstanceWithoutConstructor();
        $this->assertInstanceOf(OpenSRS::class, $instance);
    }

    /**
     * Test that the module property defaults to 'domains'.
     *
     * @return void
     */
    public function testModulePropertyDefaultsToDomains(): void
    {
        $instance = $this->reflection->newInstanceWithoutConstructor();
        $this->assertSame('domains', $instance->module);
    }

    /**
     * Test that the error_levels property is set correctly.
     *
     * @return void
     */
    public function testErrorLevelsPropertyIsArray(): void
    {
        $instance = $this->reflection->newInstanceWithoutConstructor();
        $this->assertIsArray($instance->error_levels);
        $this->assertArrayHasKey(LIBXML_ERR_WARNING, $instance->error_levels);
        $this->assertArrayHasKey(LIBXML_ERR_ERROR, $instance->error_levels);
        $this->assertArrayHasKey(LIBXML_ERR_FATAL, $instance->error_levels);
    }

    /**
     * Test that error_levels maps to correct string labels.
     *
     * @return void
     */
    public function testErrorLevelsMapToCorrectLabels(): void
    {
        $instance = $this->reflection->newInstanceWithoutConstructor();
        $this->assertSame('Warning', $instance->error_levels[LIBXML_ERR_WARNING]);
        $this->assertSame('Error', $instance->error_levels[LIBXML_ERR_ERROR]);
        $this->assertSame('Fatal Error', $instance->error_levels[LIBXML_ERR_FATAL]);
    }

    /**
     * Test that all expected public properties exist on the class.
     *
     * @return void
     */
    public function testExpectedPublicPropertiesExist(): void
    {
        $expectedProperties = [
            'id', 'cookie', 'osrsHandlerAllInfo', 'osrsHandlerWhoisPrivacy',
            'osrsHandlerStatus', 'osrsHandlerDnssec', 'locked', 'registrarStatus',
            'whoisPrivacy', 'expiryDate', 'dnssec', 'module', 'settings',
            'serviceInfo', 'serviceExtra', 'serviceAddons', 'error_levels',
        ];

        foreach ($expectedProperties as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "OpenSRS should have property '{$prop}'"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "Property '{$prop}' should be public"
            );
        }
    }

    /**
     * Test getEventTypes returns a properly structured array.
     *
     * @return void
     */
    public function testGetEventTypesReturnsStructuredArray(): void
    {
        $result = OpenSRS::getEventTypes();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('types', $result);
        $this->assertArrayHasKey('common', $result);
        $this->assertArrayHasKey('uncommon', $result);
    }

    /**
     * Test getEventTypes types section contains domain, order, and transfer.
     *
     * @return void
     */
    public function testGetEventTypesContainsDomainOrderTransfer(): void
    {
        $result = OpenSRS::getEventTypes();
        $types = $result['types'];

        $this->assertArrayHasKey('domain', $types);
        $this->assertArrayHasKey('order', $types);
        $this->assertArrayHasKey('transfer', $types);
    }

    /**
     * Test getEventTypes domain types contain expected event names.
     *
     * @return void
     */
    public function testGetEventTypesDomainEventsAreComplete(): void
    {
        $result = OpenSRS::getEventTypes();
        $domainTypes = $result['types']['domain'];

        $expectedEvents = [
            'created', 'expired', 'nameserver_update', 'registered',
            'registrant_verification_status_change', 'renewed',
            'zone_check_status_change', 'deleted',
        ];

        foreach ($expectedEvents as $event) {
            $this->assertArrayHasKey($event, $domainTypes, "Domain events should include '{$event}'");
            $this->assertIsString($domainTypes[$event], "Domain event '{$event}' description should be a string");
        }
    }

    /**
     * Test getEventTypes order types contain expected event names.
     *
     * @return void
     */
    public function testGetEventTypesOrderEventsAreComplete(): void
    {
        $result = OpenSRS::getEventTypes();
        $orderTypes = $result['types']['order'];

        $this->assertArrayHasKey('claim_status_change', $orderTypes);
        $this->assertArrayHasKey('status_change', $orderTypes);
    }

    /**
     * Test getEventTypes transfer types contain expected event names.
     *
     * @return void
     */
    public function testGetEventTypesTransferEventsAreComplete(): void
    {
        $result = OpenSRS::getEventTypes();
        $transferTypes = $result['types']['transfer'];

        $this->assertArrayHasKey('status_change', $transferTypes);
    }

    /**
     * Test getEventTypes common section has correct structure.
     *
     * @return void
     */
    public function testGetEventTypesCommonSectionStructure(): void
    {
        $result = OpenSRS::getEventTypes();
        $common = $result['common'];

        $this->assertArrayHasKey('all', $common);
        $this->assertArrayHasKey('domain', $common);
        $this->assertArrayHasKey('order', $common);
        $this->assertArrayHasKey('transfer', $common);

        // 'all' should have event, event_id, event_date
        $this->assertArrayHasKey('event', $common['all']);
        $this->assertArrayHasKey('event_id', $common['all']);
        $this->assertArrayHasKey('event_date', $common['all']);
    }

    /**
     * Test getEventTypes uncommon section has correct top-level structure.
     *
     * @return void
     */
    public function testGetEventTypesUncommonSectionStructure(): void
    {
        $result = OpenSRS::getEventTypes();
        $uncommon = $result['uncommon'];

        $this->assertArrayHasKey('domain', $uncommon);
        $this->assertArrayHasKey('order', $uncommon);
        $this->assertArrayHasKey('transfer', $uncommon);
    }

    /**
     * Test response_to_array with a simple key-value item.
     *
     * @return void
     */
    public function testResponseToArraySimpleValue(): void
    {
        $input = [
            ['attr' => ['key' => 'name'], 'value' => 'example.com'],
            ['attr' => ['key' => 'status'], 'value' => 'active'],
        ];

        $result = OpenSRS::response_to_array($input);

        $this->assertSame('example.com', $result['name']);
        $this->assertSame('active', $result['status']);
    }

    /**
     * Test response_to_array with nested dt_assoc.
     *
     * @return void
     */
    public function testResponseToArrayNestedDtAssoc(): void
    {
        $input = [
            [
                'attr' => ['key' => 'attributes'],
                'dt_assoc' => [
                    'item' => [
                        ['attr' => ['key' => 'domain'], 'value' => 'test.com'],
                    ],
                ],
            ],
        ];

        $result = OpenSRS::response_to_array($input);

        $this->assertIsArray($result['attributes']);
        $this->assertSame('test.com', $result['attributes']['domain']);
    }

    /**
     * Test response_to_array with nested dt_array.
     *
     * @return void
     */
    public function testResponseToArrayNestedDtArray(): void
    {
        $input = [
            [
                'attr' => ['key' => 'items'],
                'dt_array' => [
                    'item' => [
                        ['attr' => ['key' => '0'], 'value' => 'first'],
                        ['attr' => ['key' => '1'], 'value' => 'second'],
                    ],
                ],
            ],
        ];

        $result = OpenSRS::response_to_array($input);

        $this->assertIsArray($result['items']);
        $this->assertSame('first', $result['items']['0']);
        $this->assertSame('second', $result['items']['1']);
    }

    /**
     * Test response_to_array with a single attr-wrapped item.
     *
     * @return void
     */
    public function testResponseToArraySingleAttrItem(): void
    {
        $input = ['attr' => ['key' => 'single'], 'value' => 'only_value'];

        $result = OpenSRS::response_to_array($input);

        $this->assertSame('only_value', $result['single']);
    }

    /**
     * Test response_to_array returns empty array for empty input.
     *
     * @return void
     */
    public function testResponseToArrayEmptyInput(): void
    {
        $result = OpenSRS::response_to_array([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test that the request method is public and static.
     *
     * @return void
     */
    public function testRequestMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('request');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that the xmlRequest method is public and static.
     *
     * @return void
     */
    public function testXmlRequestMethodIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('xmlRequest');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Test that getCookieRaw is public and static with correct parameters.
     *
     * @return void
     */
    public function testGetCookieRawSignature(): void
    {
        $method = $this->reflection->getMethod('getCookieRaw');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('username', $params[0]->getName());
        $this->assertSame('password', $params[1]->getName());
        $this->assertSame('domain', $params[2]->getName());
    }

    /**
     * Test that getNameserversRaw is public and static with correct parameters.
     *
     * @return void
     */
    public function testGetNameserversRawSignature(): void
    {
        $method = $this->reflection->getMethod('getNameserversRaw');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('cookie', $params[0]->getName());
    }

    /**
     * Test that createNameserverRaw is public and static with correct parameters.
     *
     * @return void
     */
    public function testCreateNameserverRawSignature(): void
    {
        $method = $this->reflection->getMethod('createNameserverRaw');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(4, $params);
        $this->assertSame('cookie', $params[0]->getName());
        $this->assertSame('hostname', $params[1]->getName());
        $this->assertSame('ip', $params[2]->getName());
        $this->assertSame('useDomain', $params[3]->getName());
        $this->assertTrue($params[3]->isOptional());
        $this->assertFalse($params[3]->getDefaultValue());
    }

    /**
     * Test that deleteNameserverRaw is public and static with correct parameters.
     *
     * @return void
     */
    public function testDeleteNameserverRawSignature(): void
    {
        $method = $this->reflection->getMethod('deleteNameserverRaw');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(4, $params);
        $this->assertSame('cookie', $params[0]->getName());
        $this->assertSame('hostname', $params[1]->getName());
        $this->assertSame('ip', $params[2]->getName());
        $this->assertSame('useDomain', $params[3]->getName());
    }

    /**
     * Test that transferCheck is public and static with correct parameters.
     *
     * @return void
     */
    public function testTransferCheckSignature(): void
    {
        $method = $this->reflection->getMethod('transferCheck');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('domain', $params[0]->getName());
        $this->assertSame('checkStatus', $params[1]->getName());
        $this->assertSame('getRequestAddress', $params[2]->getName());
        $this->assertTrue($params[1]->isOptional());
        $this->assertTrue($params[2]->isOptional());
        $this->assertSame(0, $params[1]->getDefaultValue());
        $this->assertSame(0, $params[2]->getDefaultValue());
    }

    /**
     * Test that lookupGetDomain is public and static with correct parameters.
     *
     * @return void
     */
    public function testLookupGetDomainSignature(): void
    {
        $method = $this->reflection->getMethod('lookupGetDomain');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('domain', $params[0]->getName());
        $this->assertSame('type', $params[1]->getName());
        $this->assertSame('limit', $params[2]->getName());
        $this->assertSame('all_info', $params[1]->getDefaultValue());
        $this->assertFalse($params[2]->getDefaultValue());
    }

    /**
     * Test that lookupDomain is public and static with correct parameters.
     *
     * @return void
     */
    public function testLookupDomainSignature(): void
    {
        $method = $this->reflection->getMethod('lookupDomain');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('domain', $params[0]->getName());
        $this->assertSame('selected', $params[1]->getName());
        $this->assertFalse($params[1]->getDefaultValue());
    }

    /**
     * Test that checkDomainAvailable is public and static.
     *
     * @return void
     */
    public function testCheckDomainAvailableSignature(): void
    {
        $method = $this->reflection->getMethod('checkDomainAvailable');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('domain', $params[0]->getName());
    }

    /**
     * Test that lookupDomainPrice is public and static with correct parameters.
     *
     * @return void
     */
    public function testLookupDomainPriceSignature(): void
    {
        $method = $this->reflection->getMethod('lookupDomainPrice');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('domain', $params[0]->getName());
        $this->assertSame('regType', $params[1]->getName());
        $this->assertSame('new', $params[1]->getDefaultValue());
    }

    /**
     * Test that lock method is public and static with correct parameters.
     *
     * @return void
     */
    public function testLockSignature(): void
    {
        $method = $this->reflection->getMethod('lock');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('domain', $params[0]->getName());
        $this->assertSame('lock', $params[1]->getName());
        $this->assertTrue($params[1]->getDefaultValue());
    }

    /**
     * Test that whoisPrivacy method is public and static with correct parameters.
     *
     * @return void
     */
    public function testWhoisPrivacySignature(): void
    {
        $method = $this->reflection->getMethod('whoisPrivacy');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('domain', $params[0]->getName());
        $this->assertSame('enabled', $params[1]->getName());
    }

    /**
     * Test that listDomainsByExpireyDate is public and static with correct parameters.
     *
     * @return void
     */
    public function testListDomainsByExpireyDateSignature(): void
    {
        $method = $this->reflection->getMethod('listDomainsByExpireyDate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('startDate', $params[0]->getName());
        $this->assertSame('endDate', $params[1]->getName());
        $this->assertFalse($params[0]->getDefaultValue());
        $this->assertFalse($params[1]->getDefaultValue());
    }

    /**
     * Test that redeemDomain is public and static.
     *
     * @return void
     */
    public function testRedeemDomainSignature(): void
    {
        $method = $this->reflection->getMethod('redeemDomain');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('domain', $params[0]->getName());
    }

    /**
     * Test that searchDomain is public and static with correct parameters.
     *
     * @return void
     */
    public function testSearchDomainSignature(): void
    {
        $method = $this->reflection->getMethod('searchDomain');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('domain', $params[0]->getName());
        $this->assertSame('function', $params[1]->getName());
    }

    /**
     * Test that ackEvent is public and static.
     *
     * @return void
     */
    public function testAckEventSignature(): void
    {
        $method = $this->reflection->getMethod('ackEvent');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event_id', $params[0]->getName());
    }

    /**
     * Test that pollEvent is public and static with default limit of 1.
     *
     * @return void
     */
    public function testPollEventSignature(): void
    {
        $method = $this->reflection->getMethod('pollEvent');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('limit', $params[0]->getName());
        $this->assertSame(1, $params[0]->getDefaultValue());
    }

    /**
     * Test that loadDomainInfo is a public instance method.
     *
     * @return void
     */
    public function testLoadDomainInfoIsPublicInstanceMethod(): void
    {
        $method = $this->reflection->getMethod('loadDomainInfo');
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
    }

    /**
     * Test that the constructor accepts an optional id parameter defaulting to false.
     *
     * @return void
     */
    public function testConstructorSignature(): void
    {
        $constructor = $this->reflection->getConstructor();
        $params = $constructor->getParameters();

        $this->assertCount(1, $params);
        $this->assertSame('id', $params[0]->getName());
        $this->assertTrue($params[0]->isOptional());
        $this->assertFalse($params[0]->getDefaultValue());
    }

    /**
     * Test that getEventTypes is idempotent (returns same result on multiple calls).
     *
     * @return void
     */
    public function testGetEventTypesIsIdempotent(): void
    {
        $first = OpenSRS::getEventTypes();
        $second = OpenSRS::getEventTypes();

        $this->assertSame($first, $second);
    }

    /**
     * Test response_to_array with mixed value types.
     *
     * @return void
     */
    public function testResponseToArrayMixedTypes(): void
    {
        $input = [
            ['attr' => ['key' => 'count'], 'value' => '42'],
            ['attr' => ['key' => 'flag'], 'value' => '1'],
            ['attr' => ['key' => 'nested'], 'dt_assoc' => [
                'item' => [
                    ['attr' => ['key' => 'inner'], 'value' => 'deep'],
                ],
            ]],
        ];

        $result = OpenSRS::response_to_array($input);

        $this->assertSame('42', $result['count']);
        $this->assertSame('1', $result['flag']);
        $this->assertIsArray($result['nested']);
        $this->assertSame('deep', $result['nested']['inner']);
    }

    /**
     * Test that all static methods exist that are referenced in the class.
     *
     * @return void
     */
    public function testAllExpectedStaticMethodsExist(): void
    {
        $expectedMethods = [
            'getEventTypes', 'response_to_array', 'request', 'xmlRequest',
            'getCookieRaw', 'getNameserversRaw', 'createNameserverRaw',
            'deleteNameserverRaw', 'transferCheck', 'lookupGetDomain',
            'lookupDomain', 'checkDomainAvailable', 'lookupDomainPrice',
            'searchDomain', 'lock', 'whoisPrivacy', 'listDomainsByExpireyDate',
            'redeemDomain', 'ackEvent', 'pollEvent',
        ];

        foreach ($expectedMethods as $methodName) {
            $this->assertTrue(
                $this->reflection->hasMethod($methodName),
                "OpenSRS should have method '{$methodName}'"
            );
            $method = $this->reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isStatic(),
                "Method '{$methodName}' should be static"
            );
        }
    }
}
