<?php

namespace Detain\MyAdminOpenSRS;

use Detain\MyAdminOpenSRS\OpenSRS;
use Symfony\Component\EventDispatcher\GenericEvent;

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
			self::$module.'.load_addons' => [__CLASS__, 'getAddon'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			'function.requirements' => [__CLASS__, 'getRequirements']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getAddon(GenericEvent $event) {
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
	public static function doAddonEnable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		myadmin_log(self::$module, 'info', 'OpenSRS Whois Privacy Activation', __LINE__, __FILE__);
		function_requirements('class.OpenSRS');
		OpenSRS::whoisPrivacy($serviceInfo[$settings['PREFIX'].'_hostname'], TRUE);
	}

	/**
	 * @param \ServiceHandler $serviceOrder
	 * @param                $repeatInvoiceId
	 * @param bool           $regexMatch
	 * @throws \Exception
	 */
	public static function doAddonDisable(\ServiceHandler $serviceOrder, $repeatInvoiceId, $regexMatch = FALSE) {
		$serviceInfo = $serviceOrder->getServiceInfo();
		$settings = get_module_settings(self::$module);
		function_requirements('class.OpenSRS');
		OpenSRS::whoisPrivacy($serviceInfo[$settings['PREFIX'].'_hostname'], FALSE);
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('OPENSRS')) {
			myadmin_log(self::$module, 'info', 'OpenSRS Activation', __LINE__, __FILE__);
			$return = self::activate_domain($serviceClass->getId());
			$event['success'] = $return;
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_opensrs', '/images/myadmin/to-do.png', 'ReUsable OpenSRS Licenses');
			$menu->add_link(self::$module, 'choice=none.opensrs_list', '/images/myadmin/to-do.png', 'OpenSRS Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.opensrs_licenses_list', '/images/whm/createacct.gif', 'List all OpenSRS Licenses');
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

	/**
	 * processes a domain activation
	 *
	 * @param int $id
	 * @return bool
	 */
	public static function activate_domain($id) {
		page_title('Activate Domain');
		function_requirements('class.OpenSRS');
		$settings = get_module_settings('domains');
		$db = get_module_db('domains');
		$id = (int) $id;
		$serviceTypes = run_event('get_service_types', FALSE, 'domains');
		$renew = FALSE;
		$class = '\\MyAdmin\\Orm\\'.get_orm_class_from_table($settings['TABLE']);
		/** @var \MyAdmin\Orm\Product $class **/
		$serviceClass = new $class();
		$serviceClass->load_real($id);
		if ($serviceClass->loaded === TRUE) {
			$data = $GLOBALS['tf']->accounts->read($serviceClass->getCustid());
			if ($data['status'] == 'locked') {
				dialog('Account is Locked', "The account for this domain is locked so skipping activation of {$settings['TITLE']} {$serviceClass->getId()}");
				myadmin_log('domains', 'info', "The account for this domain is locked so skipping activation of {$settings['TITLE']} {$serviceClass->getId()}", __LINE__, __FILE__);
				return FALSE;
			}
			$username = $serviceClass->getUsername();
			if (trim($username) == '') {
				$username = str_replace(['-', '.'], ['', ''], strtolower($serviceClass->getHostname()));
				$username = mb_substr($username, 0, 15);
			}
			$password = $serviceClass->getPassword();
			if (trim($password) == '')
				$password = _randomstring(20);
			$serviceInfo = $serviceTypes[$serviceClass->getType()];
			$serviceTld = $serviceInfo['services_field1'];
			$extra = parse_domain_extra($serviceClass->getExtra());
			//myadmin_log('domains', 'info', json_encode($extra), __LINE__, __FILE__);
			if ($serviceClass->getStatus() == 'active') {
				$response = \Detain\MyAdminOpenSRS\OpenSRS::lookupGetDomain($serviceClass->getHostname(), 'all_info');
				if ($response !== FALSE && isset($response['attributes']['expiredate'])) {
					$parts = explode('-', $response['attributes']['expiredate']);
					$expireyear = $parts[0];
					$expiry_full_date = $parts[0].'-'.$parts[1].'-'.$parts[2];
					myadmin_log('domains', 'info', "got expire year {$expireyear}", __LINE__, __FILE__);
					/*if (mb_strlen($expireyear) == 4 && $expireyear >= date('Y'))
						$renew = true;*/
					$date_today = date('Y-m-d');
					if (strtotime($expiry_full_date) >= strtotime($date_today))
						$renew = TRUE;
				}
			}
			$error = FALSE;
			if ($renew === TRUE) {
				$formFormat = 'json';
				$formFunction = 'provRenew';
				//$callstring = "";
				$callArray = [
					'func' => 'provRenew', 'attributes' => [
						'auto_renew' => '0',
						'currentexpirationyear' => $expireyear,
						'domain' => $serviceClass->getHostname(),
						'f_parkp' => 'N',
						'handle' => 'process',
						'period' => '1'
					]
				];
				//if ($formFormat == "array") $callstring = $callArray;
				//if ($formFormat == "json") $callstring = json_encode($callArray);
				//if ($formFormat == "yaml") $callstring = Spyc::YAMLDump($callArray);
				$callstring = json_encode($callArray);
				// Open SRS Call -> Result
				try {
					$request = new \opensrs\Request();
					$osrsHandler = $request->process($formFormat, $callstring);
				} catch (\opensrs\APIException $e) {
					myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
				} catch (\opensrs\Exception $e) {
					myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
				}
				request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provRenew', $callstring, $osrsHandler);
				// Print out the results
				myadmin_log('domains', 'info', 'In: '.$callstring.'<br>', __LINE__, __FILE__);
				myadmin_log('domains', 'info', 'Out: '.json_encode($osrsHandler->resultFullRaw), __LINE__, __FILE__);
				//myadmin_log('domains', 'info', "Out: ". $osrsHandler->resultFullFormatted, __LINE__, __FILE__);
				if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
					$extra['order'] = obj2array($osrsHandler->resultFullRaw);
					if ($osrsHandler->resultFullRaw['is_success'] == 1) {
						$order_id = $osrsHandler->resultFullRaw['attributes']['order_id'];
						$domain_id = $osrsHandler->resultFullRaw['attributes']['id'];
						$extra['order_id'] = $order_id;
						$extra['domain_id'] = $domain_id;
					} else {
						$error = get_domain_error_text($osrsHandler);
					}
				}
			} else {
				// else new registration
				$formFormat = 'json';
				$formFunction = 'provSWregister';
				//$callstring = "";
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
						if (mb_substr($phone, 0, mb_strlen($code)) != $code)
							$phone = '+'.$code.'.'.$phone;
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
						if (preg_match("/^webhosting(?P<id>[\d]*)\./", $db2->Record['website_name'], $matches) && $matches['id'] >= 2003) {
							$dns1 = 'dns'.$matches['id'].'a.trouble-free.net';
							$dns2 = 'dns'.$matches['id'].'b.trouble-free.net';
						} else {
							$dns1 = 'dns.trouble-free.net';
							$dns2 = 'dns2.trouble-free.net';
						}
						$dns3 = '';
					}
				}
				$dns_array = [$dns1, $dns2, $dns3];
				$dns_string = $dns1.','.$dns2.($dns3 != '' ? ','.$dns3 : '');
				$country = convert_country_iso2($serviceClass->getCountry());
				$callArray = [
					'func' => 'provSWregister',
					'personal' => [
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
					],
					'data' => [
						'reg_username' => $username,
						'reg_password' => $password,
						'auto_renew' => '0',
						'domain' => $serviceClass->getHostname(),
						'reg_type' => 'new',
						'period' => '1',
						'custom_tech_contact' => '1',
						'custom_nameservers' => 1,
						'name1' => $dns1,
						'sortorder1' => '1',
						'name2' => $dns2,
						'sortorder2' => '2',
						'name3' => $dns3,
						'sortorder3' => '3',
						/* optional start  */
						'reg_domain' => '',
						'affiliate_id' => '',
						'f_parkp' => 'N',
						//"f_whois_privacy" => "1",
						'f_whois_privacy' => '0',
						'f_lock_domain' => '0',
						'link_domains' => '0',
						'encoding_type' => '',
						'change_contact' => '',
						'master_order_id' => '',
						'handle' => '',
						'custom_transfer_nameservers' => 0
					]
				];
				if (trim($serviceClass->getFax()) != '')
					$callArray['fax'] = $serviceClass->getFax();
				if (in_array($serviceTld, ['.abogado', '.aero', '.asia', '.cl', '.co.hu', '.com.ar', '.com.br', '.com.lv', '.com.mx', '.com.pt', '.com.ro', '.coop', '.co.za', '.de', '.dk', '.es', '.fr', '.hk', '.hu', '.it', '.jobs', '.law', '.lv', '.mx', '.my', '.no', '.nu', '.nyc', '.pm', '.pro', '.pt', '.re', '.ro', '.ru', '.se', '.sg', '.tf', '.travel', '.uk', '.us', '.wf', '.xxx', '.yt'])) {
					$tld_data = TRUE;
					$callArray['data']['tld_data'] = [];
					if (in_array($serviceTld, ['.abogado', '.aero', '.cl', '.co.hu', '.com.ar', '.com.lv', '.com.mx', '.com.pt', '.com.ro', '.coop', '.co.za', '.de', '.dk', '.es', '.fi.', '.fr', '.hk', '.hu', '.jobs', '.law', '.lv', '.mx', '.my', '.no', '.nu', '.nyc', '.pm', '.pt', '.re', '.ro', '.ru', '.se', '.sg', '.tf', '.travel', '.wf', '.yt'])) {
						$callArray['data']['tld_data']['registrant_extra_info'] = [];
						//.nu
						if (in_array($serviceTld, ['.nu', '.hu', '.co.hu', '.se'])) {
							myadmin_log('domains', 'info', 'adding registrant type', __LINE__, __FILE__);
							if (isset($extra['registrant_type']))
								$callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] = $extra['registrant_type'];
							else
								$callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] = 'individual';
							if ($callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] == 'individual')
								$callArray['data']['tld_data']['registrant_extra_info']['id_card_number'] = $extra['id_card_number'];
							else {
								$callArray['data']['tld_data']['registrant_extra_info']['registrant_vat_id'] = $extra['registrant_vat_id'];
								$callArray['data']['tld_data']['registrant_extra_info']['registration_number'] = $extra['registration_number'];
							}
						}
						//.hk .ru
						if (in_array($serviceTld, ['.hk', '.ru'])) {
							myadmin_log('domains', 'info', 'adding registrant type', __LINE__, __FILE__);
							if (isset($extra['registrant_type']))
								$callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] = $extra['registrant_type'];
							else
								$callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] = 'individual';
							if ($callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] == 'individual') {
								$callArray['data']['tld_data']['registrant_extra_info']['id_card_number'] = $extra['id_card_number'];
								$callArray['data']['tld_data']['registrant_extra_info']['date_of_birth'] = $extra['date_of_birth'];
							} else {
								$callArray['data']['tld_data']['registrant_extra_info']['registration_number'] = $extra['registration_number'];
							}
						}
						if (in_array($serviceTld, ['.nyc'])) {
							myadmin_log('domains', 'info', 'adding registrant type', __LINE__, __FILE__);
							if (isset($extra['registrant_type']))
								$callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] = $extra['registrant_type'];
							else
								$callArray['data']['tld_data']['registrant_extra_info']['registrant_type'] = 'individual';
						}
						if (in_array($serviceTld, ['.fr'])) {
							myadmin_log('domains', 'info', 'adding registrant type', __LINE__, __FILE__);
							$extra_info = [];
							if (isset($extra['registrant_type']))
								$extra_info['registrant_type'] = $extra['registrant_type'];
							else
								$extra_info['registrant_type'] = 'individual';
							if ($extra_info['registrant_type'] == 'individual') {
								$extra_info['country_of_birth'] = $extra['country_of_birth'];
								$extra_info['date_of_birth'] = $extra['country_of_birth'];
								if (mb_strtoupper($extra_info['country_of_birth']) == 'FR') {
									$extra_info['place_of_birth'] = $extra['country_of_birth'];
									$extra_info['postal_code_of_birth'] = $extra['country_of_birth'];
								}
							}
							$callArray['data']['tld_data']['registrant_extra_info'] = $extra_info;
						}
					} else {
						// .asia Domains
						if ($serviceTld == '.asia') {
							$callArray['data']['tld_data']['cedinfo'] = [
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
						// .us Domains
						if ($serviceTld == '.us') {
							$callArray['data']['tld_data']['nexus'] = [
								'app_purpose' => $extra['app_purpose'],
								'category' => $extra['category']
							];
							if (trim($extra['validator']) != '' && in_array($extra['category'], ['C31', 'C32']))
								$callArray['data']['tld_data']['nexus']['validator'] = $extra['validator'];
						}
					}
				}
				if ($callArray['data']['reg_type'] == 'premium') {
					$callArray['data']['premium_price_to_verify'] = '';
				}
				if (isset($extra['transfer']) && $extra['transfer'] == 'yes') {
					$callArray['data']['custom_nameservers'] = '0';
					$callArray['data']['reg_type'] = 'transfer';
					myadmin_log('domains', 'info', 'Transfer: YES', __LINE__, __FILE__);
					//if ($serviceTld == '.com.ph')
	//						$callArray['data']['period'] = 0;
				} else {
					myadmin_log('domains', 'info', 'Transfer: NO', __LINE__, __FILE__);
				}
				if ($serviceTld == '.eu') {
					$callArray['data']['custom_transfer_nameservers'] = '1';
				}
				//if ($serviceTld == 'fr')
					//$callArray['data']['registrant_extra_info'] = $callArray['registrant_extra_info'];
				if (in_array($serviceTld, ['.eu', '.be', '.de'])) {
					//$callArray['personal']['entity_type'] = 2;
					$callArray['data']['eu_country'] = $extra['domain_country'];
					$callArray['data']['lang'] = $extra['lang'];
					$callArray['data']['owner_confirm_address'] = $extra['owner_confirm_address'];
					//$callArray['data']["owner_confirm_address"] = $callArray['personal']['email'];
				}
				if ($serviceTld == '.ca') {
					$callArray['data']['ca_link_domain'] = (isset($extra['ca_link_domain']) ? $extra['ca_link_domain'] : '');
					$callArray['data']['cwa'] = (isset($extra['cwa']) ? $extra['cwa'] : '');
					$callArray['data']['domain_description'] = (isset($extra['domain_description']) ? $extra['domain_description'] : '');
					$callArray['data']['isa_trademark'] = (isset($extra['isa_trademark']) ? $extra['isa_trademark'] : 'N');
					$callArray['data']['lang_pref'] = (isset($extra['lang_pref']) ? $extra['lang_pref'] : 'EN');
					$callArray['data']['legal_type'] = (isset($extra['legal_type']) ? $extra['legal_type'] : 'CCT');
					$callArray['data']['rant_agrees'] = (isset($extra['rant_agrees']) ? $extra['rant_agrees'] : '');
					$callArray['data']['rant_no'] = (isset($extra['rant_no']) ? $extra['rant_no'] : '');
				}
				if ($serviceTld == '.tel') {
					$callArray['data']['custom_nameservers'] = 0;
					$callArray['data']['custom_nameservers'] = 0;
				}
				if ($serviceTld == '.pro') {
					$callArray['professional_data'] = ['profession' => 'Administrator'];
					$callArray['data']['professional_data'] = $callArray['professional_data'];
				}
				if ($serviceTld == '.it') {
					$callArray['data']['tld_data']['it_registrant_info'] = ['entity_type' => $extra['entity_type'], 'reg_code' => $extra['reg_code']];
				}
				if ($serviceTld == '.name')
					$callArray['data']['forwarding_email'] = $extra['forwarding_email'];

				//if ($formFormat == "array") $callstring = $callArray;
				//if ($formFormat == "json") $callstring = json_encode($callArray);
				//if ($formFormat == "yaml") $callstring = Spyc::YAMLDump($callArray);
				unset($osrsHandler);
				unset($error);
				$callstring = json_encode($callArray);
				//$callstring = json_encode($callArray, JSON_PRETTY_PRINT);
				// Open SRS Call -> Result
				// Print out the results
				myadmin_log('domains', 'info', ' In: '.$callstring, __LINE__, __FILE__);
				try {
					$request = new \opensrs\Request();
					$osrsHandler = $request->process($formFormat, $callstring);
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provSWregister', $callstring, $osrsHandler);
					myadmin_log('domains', 'info', 'Out: '.json_encode($osrsHandler), __LINE__, __FILE__);
				} catch (\opensrs\APIException $e) {
					$error = $e->getMessage();
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provSWregister', $callstring, $e->getMessage());
					myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
				} catch (\opensrs\Exception $e) {
					$error = $e->getMessage();
					request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provSWregister', $callstring, $e->getMessage());
					myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
				}
				/*
				$arr = obj2array($osrsHandler->resultFullRaw);
				foreach ($arr as $key => $value) {
				myadmin_log('domains', 'info', "Out: $key => " . json_encode($value), __LINE__, __FILE__);
				}
				*/
				if ((!isset($error) || $error === FALSE) && isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
					$extra['order'] = obj2array($osrsHandler->resultFullRaw);
					if ($osrsHandler->resultFullRaw['is_success'] == 1) {
						$order_id = $osrsHandler->resultFullRaw['attributes']['id'];
						$extra['order_id'] = $order_id;

						if (!isset($error) || $error === FALSE) {
							unset($osrsHandler);
							$callArray = ['func' => 'provProcessPending', 'attributes' => ['order_id' => $order_id]];
							$callstring = json_encode($callArray);
							try {
								$request = new \opensrs\Request();
								$osrsHandler = $request->process('json', $callstring);
							} catch (\opensrs\APIException $e) {
								myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
							} catch (\opensrs\Exception $e) {
								myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
							}
							request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'provProcessPending', $callstring, $osrsHandler);
							myadmin_log('domains', 'info', ' In: '.$callstring.'<br>', __LINE__, __FILE__);
							myadmin_log('domains', 'info', 'Out:'.json_encode($osrsHandler), __LINE__, __FILE__);
							if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
								if ($osrsHandler->resultFullRaw['is_success'] == 1) {
									$order_id = $osrsHandler->resultFullRaw['attributes']['order_id'];
									$domain_id = $osrsHandler->resultFullRaw['attributes']['id'];
									$extra['order_id'] = $order_id;
									$extra['domain_id'] = $domain_id;
								} else {
									$error = get_domain_error_text($osrsHandler);
								}
								$extra['provProcessPending'] = obj2array($osrsHandler->resultFullRaw);
							}
							if ((!isset($error) || $error === FALSE) && isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
								$callstring = '';
								$callArray = [
									'func' => 'nsAdvancedUpdt', 'attributes' => [
									'domain' => $serviceClass->getHostname(),
									'op_type' => 'assign',
									'assign_ns' => $dns_array
									]
								];
								$callstring = json_encode($callArray);
								try {
									$request = new \opensrs\Request();
									request_log('domains', $serviceClass->getCustid(), __FUNCTION__, 'opensrs', 'nsAdvancedUpdt', $callstring, $osrsHandler);
									myadmin_log('domains', 'info', 'In: '.$callstring.'<br>', __LINE__, __FILE__);
									myadmin_log('domains', 'info', 'Out: '.json_encode($request->process('json', $callstring)), __LINE__, __FILE__);
								} catch (\opensrs\APIException $e) {
									myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
								} catch (\opensrs\Exception $e) {
									myadmin_log('domains', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
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
			if ((isset($error) && $error !== FALSE) /*&& isset($osrsHandler) && isset($osrsHandler->resultFullRaw)*/) {
				if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw) && isset($osrsHandler->resultFullRaw['response_text']))
					$error .= '<br>'.get_domain_error_text($osrsHandler);
				dialog('Domain Registration Error', nl2br($error), FALSE, '{width: "auto"}');
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.EMAIL_NEWLINE;
				$subject = 'Error Registering Domain '.$serviceClass->getHostname();
				$email = 'There was an error registering your domain '.$serviceClass->getHostname().'<br>
<br>
The Error message from the registrar was:<br>
'.nl2br($error).'<br>
<br>
To fix this and help ensure your domain registration goes through smoothly please<br>
update the appropriate info at this url:<br>
<a href="https://'.DOMAIN.URLDIR.$GLOBALS['tf']->link('/index.php', 'choice=none.view_domain&id='.$id).'">https://'.DOMAIN.URLDIR.$GLOBALS['tf']->link('/index.php',
				'choice=none.view_domain&id='.$id).'</a><br>
and then contact support@interserver.net to have them try the domain registration again.<br>
<br>
Interserver, Inc.<br>
';
				multi_mail($serviceClass->getEmail(), $subject, $email, $headers, 'admin/domain_error.tpl');
				//admin_mail($subject, $subject . "<br>" . nl2br(print_r($osrsHandler->resultFullRaw, TRUE)), $headers, FALSE, 'admin/domain_error.tpl');
				myadmin_log('domains', 'info', $subject, __LINE__, __FILE__);
				$serviceClass->setStatus('pending');
				myadmin_log('domains', 'info', 'Status changed to pending.', __LINE__, __FILE__);
				return FALSE;
			}
			domain_welcome_email($id);
			return TRUE;
		}
		return FALSE;
	}

}
