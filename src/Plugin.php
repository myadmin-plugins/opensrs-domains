<?php

namespace Detain\MyAdminOpenSRS;

use Symfony\Component\EventDispatcher\GenericEvent;
use Detain\MyAdminOpenSRS\OpenSRS;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminOpenSRS
 */
class Plugin {

	public static $name = 'OpenSRS Domains';
	public static $description = 'Allows selling of OpenSRS Server and VPS License Types.  More info at https://www.netenberg.com/opensrs.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a OpenSRS license. Allow 10 minutes for activation.';
	public static $module = 'domains';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			'function.requirements' => [__CLASS__, 'getRequirements'],
			self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
			self::$module.'.settings' => [__CLASS__, 'getSettings']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getAddon(GenericEvent $event) {
		$service = $event->getSubject();
		function_requirements('class.Addon');
		$addon = new \Addon();
		$addon->setModule(self::$module)
			->set_text('Whois Privacy')
			->set_cost(OPENSRS_PRIVACY_COST)
			->set_enable([__CLASS__, 'doEnable'])
			->set_disable([__CLASS__, 'doDisable'])
			->register();
		$service->addAddon($addon);
	}

	/**
	 * @param \Service_Order $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 * @throws \Exception
	 */
	public static function doEnable(\Service_Order $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		myadmin_log(self::$module, 'info', 'OpenSRS Whois Privacy Activation', __LINE__, __FILE__);
		function_requirements('class.OpenSRS');
		OpenSRS::whois_privacy($serviceInfo[$settings['PREFIX'].'_hostname'], TRUE);
	}

	/**
	 * @param \Service_Order $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 * @throws \Exception
	 */
	public static function doDisable(\Service_Order $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		function_requirements('class.OpenSRS');
		OpenSRS::whois_privacy($serviceInfo[$settings['PREFIX'].'_hostname'], FALSE);
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('FANTASTICO')) {
			myadmin_log(self::$module, 'info', 'OpenSRS Activation', __LINE__, __FILE__);
			function_requirements('activate_opensrs');
			activate_opensrs($serviceClass->getIp(), $event['field1']);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_opensrs', 'icons/database_warning_48.png', 'ReUsable OpenSRS Licenses');
			$menu->add_link(self::$module, 'choice=none.opensrs_list', 'icons/database_warning_48.png', 'OpenSRS Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.opensrs_licenses_list', 'whm/createacct.gif', 'List all OpenSRS Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('class.OpenSRS', '/../vendor/detain/myadmin-opensrs-domains/src/OpenSRS.php', '\\Detain\\MyAdminOpenSRS\\');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
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
