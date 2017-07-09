<?php

namespace Detain\MyAdminOpensrs;

use Detain\Opensrs\Opensrs;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Opensrs Domains';
	public static $description = 'Allows selling of Opensrs Server and VPS License Types.  More info at https://www.netenberg.com/opensrs.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a opensrs license. Allow 10 minutes for activation.';
	public static $module = 'domains';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'domains.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log(self::$module, 'info', 'Opensrs Activation', __LINE__, __FILE__);
			function_requirements('activate_opensrs');
			activate_opensrs($serviceClass->getIp(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_opensrs', 'icons/database_warning_48.png', 'ReUsable Opensrs Licenses');
			$menu->add_link(self::$module, 'choice=none.opensrs_list', 'icons/database_warning_48.png', 'Opensrs Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.opensrs_licenses_list', 'whm/createacct.gif', 'List all Opensrs Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_opensrs_list', '/../vendor/detain/crud/src/crud/crud_opensrs_list.php');
		$loader->add_requirement('crud_reusable_opensrs', '/../vendor/detain/crud/src/crud/crud_reusable_opensrs.php');
		$loader->add_requirement('get_opensrs_licenses', '/../vendor/detain/myadmin-opensrs-domains/src/opensrs.inc.php');
		$loader->add_requirement('get_opensrs_list', '/../vendor/detain/myadmin-opensrs-domains/src/opensrs.inc.php');
		$loader->add_requirement('opensrs_licenses_list', '/../vendor/detain/myadmin-opensrs-domains/src/opensrs_licenses_list.php');
		$loader->add_requirement('opensrs_list', '/../vendor/detain/myadmin-opensrs-domains/src/opensrs_list.php');
		$loader->add_requirement('get_available_opensrs', '/../vendor/detain/myadmin-opensrs-domains/src/opensrs.inc.php');
		$loader->add_requirement('activate_opensrs', '/../vendor/detain/myadmin-opensrs-domains/src/opensrs.inc.php');
		$loader->add_requirement('get_reusable_opensrs', '/../vendor/detain/myadmin-opensrs-domains/src/opensrs.inc.php');
		$loader->add_requirement('reusable_opensrs', '/../vendor/detain/myadmin-opensrs-domains/src/reusable_opensrs.php');
		$loader->add_requirement('class.Opensrs', '/../vendor/detain/opensrs-domains/src/Opensrs.php');
		$loader->add_requirement('vps_add_opensrs', '/vps/addons/vps_add_opensrs.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'API Settings', 'opensrs_username', 'OpenSRS Username:', 'Username to use for OpenSRS API Authentication', $settings->get_setting('OPENSRS_USERNAME'));
		$settings->add_text_setting(self::$module, 'API Settings', 'opensrs_password', 'OpenSRS Password:', 'Password to use for OpenSRS API Authentication', $settings->get_setting('OPENSRS_PASSWORD'));
		$settings->add_text_setting(self::$module, 'API Settings', 'opensrs_key', 'OpenSRS API Key:', 'Password to use for OpenSRS API Authentication', $settings->get_setting('OPENSRS_KEY'));
		$settings->add_text_setting(self::$module, 'API Settings', 'opensrs_test_key', 'OpenSRS Test API Key:', 'Password to use for OpenSRS Test API Authentication', $settings->get_setting('OPENSRS_TEST_KEY'));
		$settings->add_text_setting(self::$module, 'Price Adjustments', 'opensrs_profit', 'Default Amount to add to our cost for domain registrations to pass onto the client:', 'For example, if it costs us $6 to register a .site domain and this is set to 3, it would cost a client $9', $settings->get_setting('OPENSRS_PROFIT'));
		$settings->add_text_setting(self::$module, 'Price Adjustments', 'opensrs_privacy_cost', 'How much to charge for Whois Privacy on a domain', 'OpenSRS Charges for this so make sure you at least charge what they charge!', $settings->get_setting('OPENSRS_PRIVACY_COST'));
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_opensrs_domains', 'Out Of Stock OpenSRS Domains', 'Enable/Disable Sales Of This Type', OUTOFSTOCK_OPENSRS_DOMAINS, ['0', '1'], ['No', 'Yes']);
	}

}
