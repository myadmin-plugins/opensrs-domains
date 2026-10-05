<?php

declare(strict_types=1);

namespace Detain\MyAdminOpenSRS\Tests;

use Detain\MyAdminOpenSRS\OpenSRS;
use Detain\MyAdminOpenSRS\Plugin;
use PHPUnit\Framework\TestCase;

/**
 * MyAdmin plan_2way §5.4 and Q13: the registrar password is opened right before
 * an OpenSRS login (plaintext unchanged), one that will not open skips the
 * login, and a password activation regenerates is persisted (sealed once core's
 * domain_password write flag is on).
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RegistrarPasswordTest extends TestCase
{
    public function testWithoutCoresClassTheStoredValueIsUsedAsBefore(): void
    {
        $this->assertFalse(class_exists('MyAdmin\\Security\\ServiceSecrets', false));
        if (!class_exists('MyAdmin\\Security\\ServiceSecrets')) {
            $this->assertSame("Reg'Pw\\1", OpenSRS::registrarPassword(['domain_id' => 5, 'domain_password' => "Reg'Pw\\1"]));
            $this->assertNull(OpenSRS::registrarPassword([]));
        } else {
            $this->markTestSkipped('a ServiceSecrets class is autoloadable here');
        }
    }

    public function testThroughCoresReaderPlaintextIsUnchangedAndAnEnvelopeNeverReachesOpenSrs(): void
    {
        eval('namespace MyAdmin\\Security; class SecretBoxException extends \\RuntimeException {} final class ServiceSecrets {
            public static function readColumn($t, $c, $ref, $stored) {
                if (is_string($stored) && strncmp($stored, "S1.", 3) === 0) { throw new SecretBoxException("secretbox domain_password domains.domain_password.$ref: envelope failed authentication"); }
                return $stored;
            }
            public static function updateValue($t, $c, $ref, $plain) { return "sealed:$t.$c.$ref"; }
        }');
        $this->assertSame('Plain-Pw', OpenSRS::registrarPassword(['domain_id' => 5, 'domain_password' => 'Plain-Pw']));
        $this->assertFalse(OpenSRS::registrarPassword(['domain_id' => 5, 'domain_password' => 'S1.k1.' . str_repeat('A', 60)]));
        $m = new \ReflectionMethod(Plugin::class, 'passwordValue');
        $m->setAccessible(true);
        $this->assertSame('sealed:domains.domain_password.9', $m->invoke(null, 'domains', 'domain_password', 9, 'New-Pw'), 'the seal is delegated to core');
    }

    public function testCallSites(): void
    {
        $root = dirname(__DIR__);
        $osrs = (string) file_get_contents($root . '/src/OpenSRS.php');
        $this->assertStringContainsString("\$this->cookie = \$password === false ? false : \$this->getCookieRaw(\$this->serviceInfo['domain_username'], \$password, \$this->serviceInfo['domain_hostname']);", $osrs);
        $plugin = (string) file_get_contents($root . '/src/Plugin.php');
        $this->assertStringContainsString("OpenSRS::registrarPassword(['domain_id' => \$serviceClass->getId(), 'domain_password' => \$serviceClass->getPassword()])", $plugin);
        $this->assertStringContainsString("_password='\".\$db->real_escape(self::passwordValue(\$settings['TABLE'], \$settings['PREFIX'].'_password', \$id, \$password)).\"' where", $plugin);
        $this->assertStringNotContainsString('$password = $serviceClass->getPassword();', $plugin);
    }
}
