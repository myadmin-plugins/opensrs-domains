<?php

namespace Detain\MyAdminOpenSRS;

use Detain\MyAdminOpenSRS\OpenSRS;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminOpenSRS
 */
class Plugin
{
	public static $name = 'OpenSRS Domains';
	public static $description = 'Allows selling of OpenSRS Server and VPS License Types.  More info at https://www.netenberg.com/opensrs.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a OpenSRS license. Allow 10 minutes for activation.';
	public static $module = 'domains';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			'function.requirements' => [__CLASS__, 'getRequirements']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getAddon(GenericEvent $event)
	{
		/**
		 * @var \ServiceHandler $service
		 */
		$service = $event->getSubject();
		function_requirements('class.AddonHandler');
		$addon = new \AddonHandler();
		$addon->setModule(self::$module)
			->set_text('Whois Privacy')
			->set_cost(OPENSRS_PRIVACY_COST)
			->setEnable([__CLASS__, 'doAddonEnable'])
			->setDisable([__CLASS__, 'doAddonDisable'])
			->register();
		$service->addAddon($addon);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 * @throws \Exception
	 */
	public static function doAddonEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
	{
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		myadmin_log(self::$module, 'info', 'OpenSRS Whois Privacy Activation', __LINE__, __FILE__, self::$module, $serviceInfo[$settings['PREFIX'].'_id']);
		function_requirements('class.OpenSRS');
		OpenSRS::whoisPrivacy($serviceInfo[$settings['PREFIX'].'_hostname'], true);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 * @throws \Exception
	 */
	public static function doAddonDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = false)
	{
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		function_requirements('class.OpenSRS');
		OpenSRS::whoisPrivacy($serviceInfo[$settings['PREFIX'].'_hostname'], false);
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('OPENSRS')) {
			myadmin_log(self::$module, 'info', 'OpenSRS Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
			$return = self::activate_domain($serviceClass->getId());
			$event['success'] = $return;
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event)
	{
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_opensrs', '/images/myadmin/to-do.png', _('ReUsable OpenSRS Licenses'));
			$menu->add_link(self::$module, 'choice=none.opensrs_list', '/images/myadmin/to-do.png', _('OpenSRS Licenses Breakdown'));
			$menu->add_link(self::$module.'api', 'choice=none.opensrs_licenses_list', '/images/whm/createacct.gif', _('List all OpenSRS Licenses'));
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Plugins\Loader $this->loader
		 */
		$loader = $event->getSubject();
		$loader->add_requirement('class.OpenSRS', '/../vendor/detain/myadmin-opensrs-domains/src/OpenSRS.php', '\\Detain\\MyAdminOpenSRS\\');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		/**
		 * @var \MyAdmin\Settings $settings
		 **/
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, _('API Settings'), 'opensrs_username', _('OpenSRS Username'), _('Username to use for OpenSRS API Authentication'), $settings->get_setting('OPENSRS_USERNAME'));
		$settings->add_text_setting(self::$module, _('API Settings'), 'opensrs_password', _('OpenSRS Password'), _('Password to use for OpenSRS API Authentication'), $settings->get_setting('OPENSRS_PASSWORD'));
		$settings->add_text_setting(self::$module, _('API Settings'), 'opensrs_key', _('OpenSRS API Key'), _('Password to use for OpenSRS API Authentication'), $settings->get_setting('OPENSRS_KEY'));
		$settings->add_text_setting(self::$module, _('API Settings'), 'opensrs_test_key', _('OpenSRS Test API Key'), _('Password to use for OpenSRS Test API Authentication'), $settings->get_setting('OPENSRS_TEST_KEY'));
		$settings->add_text_setting(self::$module, _('Price Adjustments'), 'opensrs_profit', _('Default Amount to add to our cost for domain registrations to pass onto the client'), _('For example, if it costs us $6 to register a .site domain and this is set to 3, it would cost a client $9'), $settings->get_setting('OPENSRS_PROFIT'));
		$settings->add_text_setting(self::$module, _('Price Adjustments'), 'opensrs_privacy_cost', _('How much to charge for Whois Privacy on a domain'), _('OpenSRS Charges for this so make sure you at least charge what they charge!'), $settings->get_setting('OPENSRS_PRIVACY_COST'));
		$settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_opensrs_domains', _('Out Of Stock OpenSRS Domains'), _('Enable/Disable Sales Of This Type'), OUTOFSTOCK_OPENSRS_DOMAINS, ['0', '1'], ['No', 'Yes']);
	}

	/**
	 * processes a domain activation
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function activate_domain($id)
	{
		page_title('Activate Domain');
		function_requirements('class.OpenSRS');
		$settings = get_module_settings('domains');
		$db = get_module_db('domains');
		$id = (int) $id;
		$serviceTypes = run_event('get_service_types', false, 'domains');
		$renew = false;
		$class = '\\MyAdmin\\Orm\\'.get_orm_class_from_table($settings['TABLE']);
		/** @var \MyAdmin\Orm\Product $class **/
		$serviceClass = new $class();
		$serviceClass->load_real($id);
		if ($serviceClass->loaded === true) {
			$data = $GLOBALS['tf']->accounts->read($serviceClass->getCustid());
			if ($data['status'] == 'locked') {
				dialog('Account is Locked', "The account for this domain is locked so skipping activation of {$settings['TITLE']} {$serviceClass->getId()}");
				myadmin_log('opensrs', 'info', "The account for this domain is locked so skipping activation of {$settings['TITLE']} {$serviceClass->getId()}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
				return false;
			}
			$username = $serviceClass->getUsername();
			if (trim($username) == '') {
				$username = str_replace(['-', '.'], ['', ''], strtolower($serviceClass->getHostname()));
				$username = mb_substr($username, 0, 15);
			}
			$password = $serviceClass->getPassword();
			if (trim($password) == '' || strlen(trim($password)) < 10) {
				$password = _randomstring(20);
			}
			$serviceInfo = $serviceTypes[$serviceClass->getType()];
			$serviceTld = $serviceInfo['services_field1'];
			$extra = parse_domain_extra($serviceClass->getExtra());
			//myadmin_log('domains', 'info', json_encode($extra), __LINE__, __FILE__, self::$module, $serviceClass->getId());
			$response = \Detain\MyAdminOpenSRS\OpenSRS::lookupGetDomain($serviceClass->getHostname(), 'all_info');
			if ($response !== false && isset($response['attributes']['expiredate'])) {
				$expiry_full_date = $response['attributes']['expiredate'];
				$parts = explode('-', $expiry_full_date);
				$expireyear =  $parts[0];
				myadmin_log('domains', 'info', "Expire Date {$expiry_full_date}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
				$db->query("SELECT * FROM tld_info WHERE tld_tld = '$serviceTld'");
				if ($db->num_rows() > 0) {
					$db->next_record();
					$tld_info = $db->Record;
					$d_remains = intval($tld_info['tld_grace_period']) + intval($tld_info['tld_redemption_period']);
					$e_date = date('Y-m-d', strtotime($expiry_full_date));
					$redempt_date = date('Y-m-d', strtotime($e_date.' +'.$d_remains.' days'));
					myadmin_log('domains', 'info', "Grace Period - ".intval($tld_info['tld_grace_period']), __LINE__, __FILE__, self::$module, $serviceClass->getId());
					myadmin_log('domains', 'info', "Redemption Period - ".intval($tld_info['tld_redemption_period']), __LINE__, __FILE__, self::$module, $serviceClass->getId());
					myadmin_log('domains', 'info', "Final date for renewal - {$redempt_date}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
				} else {
					$redempt_date = date('Y-m-d', strtotime($expiry_full_date));
					myadmin_log('domains', 'info', "TLD record not found - {$serviceTld}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
					myadmin_log('domains', 'info', "Setting Final date for renewal as expiration date - {$redempt_date}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
				}
				$date_today = date('Y-m-d H:i:s');
				$date_only_today = date('Y-m-d');
				if (strtotime($expiry_full_date) >= strtotime($date_today)) {
					$renew = true;
					myadmin_log('domains', 'info', "Domain Renewal process started.", __LINE__, __FILE__, self::$module, $serviceClass->getId());
				} elseif (strtotime($redempt_date) >= strtotime($date_only_today)) {
					$renew = true;
					myadmin_log('domains', 'info', "Domain Renewal process started based on redemption date.", __LINE__, __FILE__, self::$module, $serviceClass->getId());
				} else {
					myadmin_log('domains', 'error', "Error in domain renewal domain expiration date is over!", __LINE__, __FILE__, self::$module, $serviceClass->getId());
					dialog('Domain Renewal Error', 'Domain Expiration date is over!', false, '{width: "auto"}');
				}
			}
			if ($renew === false) {
				$db->query("SELECT * FROM invoices WHERE invoices_service = $id AND invoices_module = 'domains' AND invoices_type = 1 ORDER BY invoices_date DESC LIMIT 1");
				$db->next_record();
				if ($db->Record['invoices_amount'] == '1.99') {
					$db->query("SELECT * FROM websites WHERE website_hostname = '".$db->real_escape($serviceClass->getHostname())."'");
					if ($db->num_rows() == 0) {
						dialog('Failed', 'Something went wrong. Please contact support team.');
						myadmin_log('opensrs', 'info', "Customer trying to register domain for $1.99 without webhosting order", __LINE__, __FILE__, self::$module, $serviceClass->getId());
						return false;
					}
					$website_active = false;
					while ($db->next_record(MYSQL_ASSOC)) {
						if ($db->Record['website_status'] == 'active')
							$website_active = true;
					}
					if ($website_active == false) {
						dialog('Failed', 'Kindly make payment of website '.$db->Record['website_id'].' you ordered along with this domain.');
						myadmin_log('opensrs', 'info', "Customer trying to register domain without paying webhosting order {$db->Record['website_id']}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
						return false;
					}
				}
			}
			$error = false;
			if ($renew === true) {
				$formFormat = 'json';
				$callArray = [
					'func' => 'provRenew', 
					'attributes' => [
						'auto_renew' => '0',
						'currentexpirationyear' => $expireyear,
						'domain' => $serviceClass->getHostname(),
						'f_parkp' => 'N',
						'handle' => 'process',
						'period' => '1'
					]
				];
				//if ($formFormat == "array") $callString = $callArray;
				//if ($formFormat == "json") $callString = json_encode($callArray);
				//if ($formFormat == "yaml") $callString = Spyc::YAMLDump($callArray);
				$callString = json_encode($callArray);
				// Open SRS Call -> Result
				myadmin_log('opensrs', 'info', 'In: '.$callString.'<br>', __LINE__, __FILE__, self::$module, $serviceClass->getId());
				try {
					$request = new \opensrs\Request();
					$osrsHandler = $request->process($formFormat, $callString);
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provRenew', $callString, $osrsHandler, $serviceClass->getId());
					myadmin_log('opensrs', 'info', 'Out: '.json_encode($osrsHandler), __LINE__, __FILE__, self::$module, $serviceClass->getId());
				} catch (\opensrs\APIException $e) {
					$error = $e->getMessage();
					$info = $e->getInfo();
					$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
					myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
					//add_output($error.':'.$info.'<br>');
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provRenew', $callString, $error.':'.$info, $serviceClass->getId());
				} catch (\opensrs\Exception $e) {
					$error = $e->getMessage();
					$info = $e->getInfo();
					$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
					myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
					//add_output($error.':'.$info.'<br>');
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provRenew', $callString, $error.':'.$info, $serviceClass->getId());
				}
				if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
					$extra['order'] = obj2array($osrsHandler->resultFullRaw);
					if ($osrsHandler->resultFullRaw['is_success'] == 1) {
						$orderId = $osrsHandler->resultFullRaw['attributes']['order_id'];
						$domainId = $osrsHandler->resultFullRaw['attributes']['id'];
						$extra['order_id'] = $orderId;
						$extra['domain_id'] = $domainId;
					} else {
						$error = get_domain_error_text($osrsHandler);
					}
				}
			} else {
				// else new registration
				$formFormat = 'json';
				$company = $serviceClass->getCompany();
				if (trim($company) == '') {
					$company = $serviceClass->getHostname();
					//$serviceClass->getCompany() = $company;
				}
				$phone = $serviceClass->getPhone();
				if (mb_substr($phone, 0, 1) != '+') {
					$db->query("select * from country_t where iso2='".$db->real_escape($serviceClass->getCountry())."' or iso3='".$db->real_escape($serviceClass->getCountry())."'", __LINE__, __FILE__);
					if ($db->num_rows() > 0) {
						$db->next_record(MYSQL_ASSOC);
						$code = $db->Record['calling_code'];
						if (mb_substr($phone, 0, mb_strlen($code)) != $code) {
							$phone = '+'.$code.'.'.$phone;
						}
					}
				}
				$dns1 = 'cdns1.interserver.net';
				$dns2 = 'cdns2.interserver.net';
				$dns3 = 'cdns3.interserver.net';
				if (isset($GLOBALS['modules']['webhosting'])) {
					$db2 = get_module_db('webhosting');
					$db2->query("select websites.*, website_name, website_masters.website_ip as website_server_ip from websites left join website_masters on website_server=website_masters.website_id where website_hostname='".$db2->real_escape($serviceClass->getHostname())."'", __LINE__, __FILE__);
					if ($db2->num_rows() > 0) {
						$db2->next_record(MYSQL_ASSOC);
						if (preg_match("/^webhosting(?P<id>[\d]*)\./", $db2->Record['website_name'], $matches)) {
							$dns1 = 'dns'.$matches['id'].'a.trouble-free.net';
							$dns2 = 'dns'.$matches['id'].'b.trouble-free.net';
						} elseif (preg_match("/^wordpress(?P<id>[\d]*)\./", $db2->Record['website_name'], $matches)) {
							$dns1 = 'dnswordpress'.$matches['id'].'a.trouble-free.net';
							$dns2 = 'dnswordpress'.$matches['id'].'b.trouble-free.net';
						} else {
							$dns1 = 'dns.trouble-free.net';
							$dns2 = 'dns2.trouble-free.net';
						}
						$dns3 = '';
					}
				}
				if ($dns3 != '') {
					$dns_array = [$dns1, $dns2, $dns3];
				} else {
					$dns_array = [$dns1, $dns2];
				}
				$country = convert_country_iso2($serviceClass->getCountry());
				$callArray = [
					'func' => 'provSWregister',
					'attributes' => [
						'auto_renew' => '0',
						'contact_set' => [
							'owner' => [
								'first_name' => $serviceClass->getFirstname(),
								'last_name' => $serviceClass->getLastname(),
								'org_name' => $company,
								'address1' => $serviceClass->getAddress(),
								'address2' => $serviceClass->getAddress2(),
								'address3' => $serviceClass->getAddress3(),
								'city' => $serviceClass->getCity(),
								'state' => $serviceClass->getState(),
								'postal_code' => str_replace(' ', '', $serviceClass->getZip()),
								'country' => $country,
								'phone' => $phone,
								'email' => $serviceClass->getEmail(),
								'url' => 'http://www.'.$serviceClass->getHostname(),
								'lang_pref' => 'EN'
							]
						],
						'custom_tech_contact' => '1',
						'custom_transfer_nameservers' => 0,
						'custom_nameservers' => 1,
						'domain' => $serviceClass->getHostname(),
						'f_parkp' => 'N',
						'f_whois_privacy' => '0',
						'f_lock_domain' => '0',
						'handle' => 'save',
						'link_domains' => '0',
						'nameserver_list' => [
							['name' => $dns1, 'sortorder' => 1], 
							['name' => $dns2, 'sortorder' => 2], 
							['name' => $dns3, 'sortorder' => 3] 
						],
						'period' => '1',
						'reg_username' => $username,
						'reg_password' => $password,
						'reg_type' => 'new',
				]];
				if (isset($extra['auth_info']) && trim($extra['auth_info']) != '') {
					$callArray['attributes']['auth_info'] = $extra['auth_info'];
				}				
				if (trim($serviceClass->getFax()) != '') {
					$callArray['attributes']['contact_set']['owner']['fax'] = $serviceClass->getFax();
				}
				if ($callArray['attributes']['reg_type'] == 'premium') {
					$callArray['attributes']['premium_price_to_verify'] = '';
				}
				if (isset($extra['transfer']) && $extra['transfer'] == 'yes') {
					$response = \Detain\MyAdminOpenSRS\OpenSRS::lookupDomain($serviceClass->getHostname());
					if ($response !== false) {
						if (isset($response['attributes']['status']) && $response['attributes']['status'] == 'available') {
							$available = true;
						} elseif (isset($response['attributes']['status']) && $response['attributes']['status'] == 'taken') {
							$available = false;
						}
					}
					if (!isset($available) || $available === false) {
						$callArray['attributes']['custom_nameservers'] = '0';
						$callArray['attributes']['reg_type'] = 'transfer';
						myadmin_log('opensrs', 'info', 'Transfer: YES', __LINE__, __FILE__, self::$module, $serviceClass->getId());
						//if ($serviceTld == '.com.ph')
						//	$callArray['attributes']['period'] = 0;
					} else {
						myadmin_log('opensrs', 'info', 'Transfer: YES but domain check came back available so forcing NO', __LINE__, __FILE__, self::$module, $serviceClass->getId());
					}
				} else {
					myadmin_log('opensrs', 'info', 'Transfer: NO', __LINE__, __FILE__, self::$module, $serviceClass->getId());
				}
				if ($serviceTld == '.eu') {
					$callArray['attributes']['custom_transfer_nameservers'] = '1';
				}
				//if ($serviceTld == '.fr')
				//	$callArray['attributes']['registrant_extra_info'] = $callArray['registrant_extra_info'];
				if (in_array($serviceTld, ['.eu', '.be', '.de'])) {
					//$callArray['personal']['entity_type'] = 2;
					$callArray['attributes']['eu_country'] = $extra['domain_country'];
					$callArray['attributes']['lang'] = $extra['lang'];
					$callArray['attributes']['owner_confirm_address'] = $extra['owner_confirm_address'];
					//$callArray['attributes']["owner_confirm_address"] = $callArray['personal']['email'];
				}
				if ($serviceTld == '.ca') {
					$callArray['attributes']['ca_link_domain'] = (isset($extra['ca_link_domain']) ? $extra['ca_link_domain'] : '');
					$callArray['attributes']['cwa'] = (isset($extra['cwa']) ? $extra['cwa'] : '');
					$callArray['attributes']['domain_description'] = (isset($extra['domain_description']) ? $extra['domain_description'] : '');
					$callArray['attributes']['isa_trademark'] = (isset($extra['isa_trademark']) ? $extra['isa_trademark'] : 'N');
					$callArray['attributes']['lang_pref'] = (isset($extra['lang_pref']) ? $extra['lang_pref'] : 'EN');
					$callArray['attributes']['legal_type'] = (isset($extra['legal_type']) ? $extra['legal_type'] : 'CCT');
					$callArray['attributes']['rant_agrees'] = (isset($extra['rant_agrees']) ? $extra['rant_agrees'] : '');
					$callArray['attributes']['rant_no'] = (isset($extra['rant_no']) ? $extra['rant_no'] : '');
				}
				if ($serviceTld == '.tel') {
					$callArray['attributes']['custom_nameservers'] = 0;
				}
				if (in_array($serviceTld, ['.abogado','.aero','.asia','.asn.au','.au','.cl','.co.hu','.co.za','.com.ar','.com.au','.com.br','.com.lv','.com.mx','.com.pt','.com.ro','.coop','.de','.dk','.es','.fr','.hk','.hu','.id.au','.it','.jobs','.law','.lv','.mx','.my','.name','.net.au','.no','.nu','.nyc','.org.au','.pm','.pro','.pt','.re','.ro','.ru','.se','.sg','.tf','.travel','.uk','.us','.wf','.xxx','.yt'])) {
					$callArray['attributes']['tld_data'] = [];
					if (in_array($serviceTld, ['.abogado', '.aero', '.cl', '.co.hu', '.com.ar', '.com.lv', '.com.mx', '.com.pt', '.com.ro', '.coop', '.co.za', '.de', '.dk', '.es', '.fi.', '.fr', '.hk', '.hu', '.jobs', '.law', '.lv', '.mx', '.my', '.no', '.nu', '.nyc', '.pm', '.pt', '.re', '.ro', '.ru', '.se', '.sg', '.tf', '.travel', '.wf', '.yt'])) {
						$callArray['attributes']['tld_data']['registrant_extra_info'] = [];
						//.nu
						if (in_array($serviceTld, ['.nu', '.hu', '.co.hu', '.se'])) {
							myadmin_log('opensrs', 'info', 'adding registrant type', __LINE__, __FILE__, self::$module, $serviceClass->getId());
							if (isset($extra['registrant_type'])) {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] = $extra['registrant_type'];
							} else {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] = 'individual';
							}
							if ($callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] == 'individual') {
								$callArray['attributes']['tld_data']['registrant_extra_info']['id_card_number'] = $extra['id_card_number'];
							} else {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registrant_vat_id'] = $extra['registrant_vat_id'];
								$callArray['attributes']['tld_data']['registrant_extra_info']['registration_number'] = $extra['registration_number'];
							}
						}
						//.hk .ru
						if (in_array($serviceTld, ['.hk', '.ru'])) {
							myadmin_log('opensrs', 'info', 'adding registrant type', __LINE__, __FILE__, self::$module, $serviceClass->getId());
							if (isset($extra['registrant_type'])) {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] = $extra['registrant_type'];
							} else {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] = 'individual';
							}
							if ($callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] == 'individual') {
								$callArray['attributes']['tld_data']['registrant_extra_info']['id_card_number'] = $extra['id_card_number'];
								$callArray['attributes']['tld_data']['registrant_extra_info']['date_of_birth'] = $extra['date_of_birth'];
							} else {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registration_number'] = $extra['registration_number'];
							}
						}
						if (in_array($serviceTld, ['.nyc'])) {
							myadmin_log('opensrs', 'info', 'adding registrant type', __LINE__, __FILE__, self::$module, $serviceClass->getId());
							if (isset($extra['registrant_type'])) {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] = $extra['registrant_type'];
							} else {
								$callArray['attributes']['tld_data']['registrant_extra_info']['registrant_type'] = 'individual';
							}
						}
						if (in_array($serviceTld, ['.fr'])) {
							myadmin_log('opensrs', 'info', 'adding registrant type', __LINE__, __FILE__, self::$module, $serviceClass->getId());
							$extraInfo = [];
							if (isset($extra['registrant_type'])) {
								$extraInfo['registrant_type'] = $extra['registrant_type'];
							} else {
								$extraInfo['registrant_type'] = 'individual';
							}
							if ($extraInfo['registrant_type'] == 'individual') {
								$extraInfo['country_of_birth'] = $extra['country_of_birth'];
								$extraInfo['date_of_birth'] = $extra['country_of_birth'];
								if (mb_strtoupper($extraInfo['country_of_birth']) == 'FR') {
									$extraInfo['place_of_birth'] = $extra['country_of_birth'];
									$extraInfo['postal_code_of_birth'] = $extra['country_of_birth'];
								}
							}
							$callArray['attributes']['tld_data']['registrant_extra_info'] = $extraInfo;
						}
					} else {
						if (in_array($serviceTld, ['.au', '.id.au', '.asn.au', '.net.au', '.org.au', '.com.au'])) {
							$au_registrant_info = [
								'policy_reason' => $extra['policy_reason'],
								'registrant_id_type' => $extra['registrant_id_type'],
								'registrant_id' => $extra['registrant_id'],
								'registrant_name' => $extra['registrant_name']
							];
							if (in_array($serviceTld, ['.asn.au', '.net.au', '.org.au', '.com.au'])) {
								$au_registrant_info['eligibility_id_type'] = $extra['eligibility_id_type'];
								$au_registrant_info['eligibility_id'] = $extra['eligibility_id'];
								$au_registrant_info['eligibility_name'] = $extra['eligibility_name'];
							}
							if (in_array($serviceTld, ['.com.au'])) {
								$au_registrant_info['eligibility_type'] = $extra['eligibility_type'];
							}
							$callArray['attributes']['tld_data']['au_registrant_info'] = $au_registrant_info;
						}
						if ($serviceTld == '.com.au') {
							$callArray['attributes']['tld_data']['au_registrant_info'] = [
								'policy_reason' => $extra['policy_reason'],
								'registrant_id_type' => $extra['registrant_id_type'],
								'registrant_id' => $extra['registrant_id'],
								'registrant_name' => $extra['registrant_name'],
								'eligibility_id_type' => $extra['eligibility_id_type'],
								'eligibility_id' => $extra['eligibility_id'],
								'eligibility_name' => $extra['eligibility_name'],
								'eligibility_type' => $extra['eligibility_type']
							];
						}
						if ($serviceTld == '.asia') {
							$callArray['attributes']['tld_data']['ced_info'] = [
								'contact_type' => $extra['contact_type'],
								'id_number' => $extra['id_number'],
								'id_type' => $extra['id_type'],
								'id_type_info' => $extra['id_type_info'],
								'legal_entity_type' => $extra['legal_entity_type'],
								'legal_entity_type_info' => $extra['legal_entity_type_info'],
								'locality_city' => $extra['locality_city'],
								'locality_country' => $extra['locality_country'],
								'locality_state_prov' => $extra['locality_state_prov']];
						}
						if ($serviceTld == '.us') {
							$callArray['attributes']['tld_data']['nexus'] = [
								'app_purpose' => $extra['app_purpose'],
								'category' => $extra['category']
							];
							if (trim($extra['validator']) != '' && in_array($extra['category'], ['C31', 'C32'])) {
								$callArray['attributes']['tld_data']['nexus']['validator'] = $extra['validator'];
							}
						}
						if ($serviceTld == '.pro') {
							$callArray['attributes']['tld_data']['professional_data'] = ['profession' => 'Administrator'];
						}
						if ($serviceTld == '.it') {
							$callArray['attributes']['tld_data']['it_registrant_info'] = ['entity_type' => $extra['entity_type'], 'reg_code' => $extra['reg_code']];
						}
						if ($serviceTld == '.name') {
							$callArray['attributes']['tld_data']['registrant_extra_info'] = ['forwarding_email' => $extra['forwarding_email']];
						}
					}
				}
				$callArray['attributes']['contact_set']['admin'] = $callArray['attributes']['contact_set']['owner'];
				$callArray['attributes']['contact_set']['billing'] = $callArray['attributes']['contact_set']['owner'];
				$callArray['attributes']['contact_set']['tech'] = $callArray['attributes']['contact_set']['owner'];
				//if ($formFormat == "array") $callString = $callArray;
				//if ($formFormat == "json") $callString = json_encode($callArray);
				//if ($formFormat == "yaml") $callString = Spyc::YAMLDump($callArray);
				unset($osrsHandler);
				unset($error);
				$callString = json_encode($callArray);
				//$callString = json_encode($callArray, JSON_PRETTY_PRINT);
				// Open SRS Call -> Result
				// Print out the results
				myadmin_log('opensrs', 'info', ' In: '.$callString, __LINE__, __FILE__, self::$module, $serviceClass->getId());
				try {
					$request = new \opensrs\Request();
					$osrsHandler = $request->process($formFormat, $callString);
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provSWregister', $callString, $osrsHandler, $serviceClass->getId());
					myadmin_log('opensrs', 'info', 'Out: '.json_encode($osrsHandler), __LINE__, __FILE__, self::$module, $serviceClass->getId());
				} catch (\opensrs\APIException $e) {
					$error = $e->getMessage();
					$info = $e->getInfo();
					$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
					myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
					//add_output($error.':'.$info.'<br>');
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provSWregister', $callString, $error.':'.$info, $serviceClass->getId());
				} catch (\opensrs\Exception $e) {
					$error = $e->getMessage();
					$info = $e->getInfo();
					$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
					myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
					//add_output($error.':'.$info.'<br>');
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provSWregister', $callString, $error.':'.$info, $serviceClass->getId());
				}
				/*
				$arr = obj2array($osrsHandler->resultFullRaw);
				foreach ($arr as $key => $value) {
				myadmin_log('opensrs', 'info', "Out: $key => " . json_encode($value), __LINE__, __FILE__, self::$module, $serviceClass->getId());
				}
				*/
				if ((!isset($error) || $error === false) && isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
					$extra['order'] = obj2array($osrsHandler->resultFullRaw);
					if ($osrsHandler->resultFullRaw['is_success'] == 1) {
						$orderId = $osrsHandler->resultFullRaw['attributes']['id'];
						$extra['order_id'] = $orderId;

						if (!isset($error) || $error === false) {
							unset($osrsHandler);
							$callArray = [
								'func' => 'provProcessPending', 
								'attributes' => [
									'order_id' => $orderId
							]];
							$callString = json_encode($callArray);
							myadmin_log('opensrs', 'info', ' In: '.$callString, __LINE__, __FILE__, self::$module, $serviceClass->getId());
							try {
								$request = new \opensrs\Request();
								$osrsHandler = $request->process('json', $callString);
								request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provProcessPending', $callString, $osrsHandler, $serviceClass->getId());
								myadmin_log('opensrs', 'info', 'Out: '.json_encode($osrsHandler), __LINE__, __FILE__, self::$module, $serviceClass->getId());
							} catch (\opensrs\APIException $e) {
								$error = $e->getMessage();
								$info = $e->getInfo();
								$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
								myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
								//add_output($error.':'.$info.'<br>');
								request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provProcessPending', $callString, $error.':'.$info, $serviceClass->getId());
							} catch (\opensrs\Exception $e) {
								$error = $e->getMessage();
								$info = $e->getInfo();
								$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
								myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
								//add_output($error.':'.$info.'<br>');
								request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provProcessPending', $callString, $error.':'.$info, $serviceClass->getId());
							}
							if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
								if ($osrsHandler->resultFullRaw['is_success'] == 1) {
									$orderId = $osrsHandler->resultFullRaw['attributes']['order_id'];
									$domainId = $osrsHandler->resultFullRaw['attributes']['id'];
									$extra['order_id'] = $orderId;
									$extra['domain_id'] = $domainId;
								} else {
									$error = get_domain_error_text($osrsHandler);
								}
								$extra['provProcessPending'] = obj2array($osrsHandler->resultFullRaw);
							}
							if ((!isset($error) || $error === false) && isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
								$callString = '';
								$callArray = [
									'func' => 'nsAdvancedUpdt', 
									'attributes' => [
										'domain' => $serviceClass->getHostname(),
										'op_type' => 'assign',
										'assign_ns' => $dns_array
								]];
								$callString = json_encode($callArray);
								myadmin_log('opensrs', 'info', ' In: '.$callString, __LINE__, __FILE__, self::$module, $serviceClass->getId());
								try {
									$request = new \opensrs\Request();
									$osrsHandler = $request->process('json', $callString);
									request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'nsAdvancedUpdt', $callString, $osrsHandler, $serviceClass->getId());
									myadmin_log('opensrs', 'info', 'Out: '.json_encode($osrsHandler), __LINE__, __FILE__, self::$module, $serviceClass->getId());
								} catch (\opensrs\APIException $e) {
									$error = $e->getMessage();
									$info = $e->getInfo();
									$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
									myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
									//add_output($error.':'.$info.'<br>');
									request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'nsAdvancedUpdt', $callString, $error.':'.$info, $serviceClass->getId());
								} catch (\opensrs\Exception $e) {
									$error = $e->getMessage();
									$info = $e->getInfo();
									$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
									myadmin_log('opensrs', 'error', $callString.':'.$error.':'.$info, __LINE__, __FILE__, self::$module, $serviceClass->getId());
									//add_output($error.':'.$info.'<br>');
									request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'nsAdvancedUpdt', $callString, $error.':'.$info, $serviceClass->getId());
								}
							}
						}
					} else {
						$error = get_domain_error_text($osrsHandler);
					}
				}
			}
			$query = "update {$settings['TABLE']} set domain_extra='".$db->real_escape(myadmin_stringify($extra))."' where domain_id=$id";
			$db->query($query, __LINE__, __FILE__);
			if ((isset($error) && $error !== false) /*&& isset($osrsHandler) && isset($osrsHandler->resultFullRaw)*/) {
				if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw) && isset($osrsHandler->resultFullRaw['response_text'])) {
					$error .= '<br>'.get_domain_error_text($osrsHandler);
				}
				//dialog('Domain Registration Error', nl2br($error), false, '{width: "auto"}');
				$subject = 'Error Registering Domain '.$serviceClass->getHostname();
				$email = 'There was an error registering your domain '.$serviceClass->getHostname().'<br>
<br>
The Error message from the registrar was:<br>
'.nl2br($error).'<br>
<br>
To fix this and help ensure your domain registration goes through smoothly please<br>
update the appropriate info at this url:<br>
<a href="https://'.DOMAIN.$GLOBALS['tf']->link('/index.php', 'choice=none.view_domain&id='.$id).'">https://'.DOMAIN.$GLOBALS['tf']->link(
					'/index.php',
					'choice=none.view_domain&id='.$id
				).'</a><br>
and then contact support@interserver.net to have them try the domain registration again.<br>
<br>
Interserver, Inc.<br>
';
				(new \MyAdmin\Mail())->multiMail($subject, $email, $serviceClass->getEmail(), 'admin/domain_error.tpl');
				//(new \MyAdmin\Mail())->adminMail($subject, $subject . "<br>" . nl2br(print_r($osrsHandler->resultFullRaw, $headers, FALSE, 'admin/domain_error.tpl');
				myadmin_log('opensrs', 'info', $subject, __LINE__, __FILE__, self::$module, $serviceClass->getId());
				$serviceClass->setStatus('pending')->save();
				myadmin_log('opensrs', 'info', 'Status changed to pending.', __LINE__, __FILE__, self::$module, $serviceClass->getId());
				return false;
			}
			domain_welcome_email($id, $renew);
			return true;
		}
		return false;
	}
}
