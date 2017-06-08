<?php

namespace Detain\MyAdminOpensrs;

use Detain\Opensrs\Opensrs;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Activate(GenericEvent $event) {
		// will be executed when the licenses.license event is dispatched
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Opensrs Activation', __LINE__, __FILE__);
			function_requirements('activate_opensrs');
			activate_opensrs($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$opensrs = new Opensrs(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $opensrs->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Opensrs editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function Menu(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_opensrs', 'icons/database_warning_48.png', 'ReUsable Opensrs Licenses');
			$menu->add_link($module, 'choice=none.opensrs_list', 'icons/database_warning_48.png', 'Opensrs Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.opensrs_licenses_list', 'whm/createacct.gif', 'List all Opensrs Licenses');
		}
	}

	public static function Requirements(GenericEvent $event) {
		// will be executed when the licenses.loader event is dispatched
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

	public static function Settings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'Opensrs', 'opensrs_username', 'Opensrs Username:', 'Opensrs Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting('licenses', 'Opensrs', 'opensrs_password', 'Opensrs Password:', 'Opensrs Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'Opensrs', 'outofstock_licenses_opensrs', 'Out Of Stock Opensrs Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes', ));
	}

}
