<?php

namespace Detain\MyAdminOpenSRS\Tests;

use Detain\MyAdminOpenSRS\OpenSRS;
use PHPUnit\Framework\TestCase;

/**
 * OpenSRS call payloads are written to the log and request_log tables; the
 * copies written there must not carry the domain management password, the
 * session cookie or the transfer auth code.
 *
 * @covers \Detain\MyAdminOpenSRS\OpenSRS::redactCallArray
 * @covers \Detain\MyAdminOpenSRS\OpenSRS::redactCall
 */
class OpenSRSLogRedactionTest extends TestCase
{
    private function registerCall(): array
    {
        return [
            'func' => 'provSWregister',
            'attributes' => [
                'domain' => 'example.com',
                'reg_username' => 'exampleuser',
                'reg_password' => 'SuperSecret1234567890',
                'auth_info' => 'EPP-CODE-123',
                'contact_set' => ['owner' => ['first_name' => 'Joe', 'email' => 'joe@example.com']],
                'tld_data' => ['registrant_extra_info' => ['aero_ens_password' => 'AeroPass1']],
                'custom_nameservers' => 0,
            ],
        ];
    }

    public function testRedactCallArrayMasksSecretsAtAnyDepth(): void
    {
        $out = OpenSRS::redactCallArray($this->registerCall());
        $this->assertSame('[redacted]', $out['attributes']['reg_password']);
        $this->assertSame('[redacted]', $out['attributes']['auth_info']);
        $this->assertSame('[redacted]', $out['attributes']['tld_data']['registrant_extra_info']['aero_ens_password']);
        $this->assertSame('exampleuser', $out['attributes']['reg_username']);
        $this->assertSame('example.com', $out['attributes']['domain']);
        $this->assertSame('joe@example.com', $out['attributes']['contact_set']['owner']['email']);
        $this->assertSame(0, $out['attributes']['custom_nameservers']);
        $this->assertSame('provSWregister', $out['func']);
    }

    public function testRedactCallArrayMasksTheCookie(): void
    {
        $out = OpenSRS::redactCallArray(['func' => 'nsGet', 'attributes' => ['cookie' => 'abc123cookie', 'all' => 1]]);
        $this->assertSame('[redacted]', $out['attributes']['cookie']);
        $this->assertSame(1, $out['attributes']['all']);
    }

    public function testRedactCallArrayKeepsEmptyValues(): void
    {
        $out = OpenSRS::redactCallArray(['attributes' => ['reg_password' => '']]);
        $this->assertSame('', $out['attributes']['reg_password']);
    }

    public function testRedactCallAcceptsArrayOrJsonAndNeverLeaks(): void
    {
        $call = $this->registerCall();
        foreach ([$call, json_encode($call)] as $input) {
            $logged = OpenSRS::redactCall($input);
            $this->assertIsString($logged);
            $this->assertStringNotContainsString('SuperSecret1234567890', $logged);
            $this->assertStringNotContainsString('EPP-CODE-123', $logged);
            $this->assertStringNotContainsString('AeroPass1', $logged);
            $this->assertStringContainsString('exampleuser', $logged);
        }
    }

    public function testRedactCallDoesNotEchoAnUnparsableString(): void
    {
        $this->assertStringNotContainsString('reg_password=Secret', OpenSRS::redactCall('reg_password=Secret'));
    }

    public function testRedactCallDoesNotModifyTheSentCall(): void
    {
        $call = $this->registerCall();
        OpenSRS::redactCall($call);
        $this->assertSame('SuperSecret1234567890', $call['attributes']['reg_password']);
    }
}
