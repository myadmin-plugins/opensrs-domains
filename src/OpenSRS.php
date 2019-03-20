<?php
/**
 * OpenSRS Domain Related Functionality
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Domains
 */
namespace Detain\MyAdminOpenSRS;

require_once __DIR__.'/openSRS_loader.php';
require_once __DIR__.'/../../../workerman/statistics/Applications/Statistics/Clients/StatisticClient.php';
use opensrs\APIException;
use opensrs\Exception;
use opensrs\Request;

/**
 * OpenSRS Domain Class
 * @access public
 */
class OpenSRS
{
	public $id;
	public $cookie;
	public $osrsHandlerAllInfo;
	public $osrsHandlerWhoisPrivacy;
	public $osrsHandlerStatus;
	public $locked;
	public $registrarStatus;
	public $whoisPrivacy;
	public $expiryDate;
	public $module = 'domains';
	public $settings;
	public $serviceInfo;
	public $serviceExtra;
	public $serviceAddons;
	public $error_levels = [LIBXML_ERR_WARNING => 'Warning', LIBXML_ERR_ERROR => 'Error', LIBXML_ERR_FATAL => 'Fatal Error'];

	/**
	 * OpenSRS::OpenSRS()
	 *
	 * @param bool $id
	 * @internal param string $username the API username
	 * @internal param string $password the API password
	 * @internal param bool $testing optional (defaults to false) testing
	 */
	public function __construct($id = false)
	{
		$this->settings = get_module_settings($this->module);
		if ($id != false) {
			$this->id = (int) $id;
		} elseif (isset($GLOBALS['tf']->variables->request['id'])) {
			$this->id = (int) $GLOBALS['tf']->variables->request['id'];
		} else {
			return;
		}
		$this->serviceInfo = get_service($this->id, $this->module);
		if ($this->serviceInfo === false) {
			return;
		}
		$this->serviceExtra = run_event('parse_service_extra', $this->serviceInfo[$this->settings['PREFIX'].'_extra'], $this->module);
		$this->serviceAddons = get_service_addons($this->id, $this->module);
		$this->cookie = $this->getCookieRaw($this->serviceInfo['domain_username'], $this->serviceInfo['domain_password'], $this->serviceInfo['domain_hostname']);
		$this->loadDomainInfo();
	}

	/**
	 * returns an array of various events we can receive with descriptions and logically hierarchically sorted
	 *
	 * @return array
	 */
	public static function getEventTypes()
	{
		return [
			'types' => [
				'domain' => [
					'created' => 'The domain was added to the system due to any number of actions including, registration, transfer completion, redemption, back-end import, etc.  Basically if a domain is added to our database for ANY reason, this event will be dispatched.',
					'expired' => 'The domain has reached DAY 0 without being renewed.',
					'nameserver_update' => 'The nameservers set for this domain have changed.',
					'registered' => 'A domain registration has completed.',
					'registrant_verification_status_change' => 'Notifies of a change to the registrant verification status for this domain.  This gives visibility into the state of a registrants verification.  ',
					'renewed' => 'This event will be fired whenever a renewal is processed for a domain, whther it be through auto-renewal, or a manually created renewal order.',
					'zone_check_status_change' => 'For .de and .fr, will notify when the DNS/ZONE check has passed, or failed at the registry, as the domain is in jeopardy of being disabled by the registry if it remains invalid.',
					'deleted' => 'A domain registration has been deleted.',
				],
				'order' => [
					'claim_status_change' => 'An Order status has changed',
					'status_change' => 'An Order status has changed',
				],
				'transfer' => [
					'status_change' => 'The Transfer status has been changed.',
				]
			],
			'common' => [
				'all' => [
					'event' => 'Specific Event being fired',
					'event_id' => 'Unique Identifier for this Event',
					'event_date' => 'Date in ISO8601 (UTC) format',
				],
				'domain' => [
					'domain_id' => 'Unique Domain Identifier',
					'domain_name' => 'The Domain Name',
				],
				'order' => [
					'order_id' => 'Unique Order Identifier',
					'domain_name' => 'The Domain Name',
				],
				'transfer' => [
					'transfer_id' => 'Unique Transfer Identifier',
					'order_id' => 'Unique Order Identifier',
					'domain_name' => 'The Domain Name',
				],
			],
			'uncommon' => [
				'domain' => [
					'created' => [
						'expiration_date' => 'Date in ISO8601 (UTC) format'
					],
					'expired' => [
						'expiration_date' => 'Date in ISO8601 (UTC) format'
					],
					'nameserver_update' => [
						'nameserver_list' => 'Array of nameservers as set at the moment of this event',
					],
					'registered' => [
						'expiration_date' => '',
						'period' => 'Number of years registered for'
					],
					'registrant_verification_status_change' => [
						'verification_status' => 'unverified pending verifying marking_as_verified verified suspended admin_reviewing bounced not_applicable',
					],
					'renewed' => [
						'expiration_date' => 'Date in ISO8601 (UTC) format',
						'period' => 'Number of years renewed for'
					],
					'zone_check_status_change' => [
						'zone_check_status' => 'valid invalid'
					],
					'deleted' => [
						'reason' => 'expired transfered auction historical by-request delete-domain-api',
						'deletion_date' => 'Date in ISO8601 (UTC) format',
						'redemption_grace_period_end_date' => 'Date in ISO8601 (UTC) format',
					],
				],
				'order' => [
					'claim_status_change' => [
						'claim_status' => 'initiated declined cancelled accepted',
					],
					'status_change' => [
						'order_status' => 'cancelled completed declined pending pending_fax processed waiting',
						'order_reg_type' => 'landrush new premium renewal sunrise transfer whois_privacy',
					],
				],
				'transfer' => [
					'status_change' => [
						'transfer_status' => 'cancelled completed pending_admin pending_owner pending_registry'
					],
				],
			],
		];
	}

	public static function response_to_array($array)
	{
		$out = [];
		if (isset($array['attr'])) {
			$array = [$array];
		}
		foreach ($array as $arrayItem) {
			$key = $arrayItem['attr']['key'];
			if (isset($arrayItem['value'])) {
				$out[$key] = $arrayItem['value'];
			} elseif (isset($arrayItem['dt_assoc'])) {
				$out[$key] = self::response_to_array($arrayItem['dt_assoc']['item']);
			} elseif (isset($arrayItem['dt_array'])) {
				//$out[$key] = self::response_to_array($arrayItem['dt_array']['item']['dt_assoc']['item']);
				$out[$key] = self::response_to_array($arrayItem['dt_array']['item']);
			}
		}
		return $out;
	}

	/**
	 * performs the common request code
	 *
	 * @param string|array $callstring array or string with the request info
	 */
	public static function request($callstring)
	{
		if (is_string($callstring)) {
			$callstring = json_decode($callstring, true);
			$action = $callstring['func'];
		} else {
			$action = $callstring['func'];
		}
		$callstring = json_encode($callstring);
		\StatisticClient::tick('OpenSRS', $action);
		try {
			$request = new Request();
			$osrsHandler = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			$info = $e->getInfo();
			$info = isset($info['error']) ? trim(implode("\n", array_unique(explode("\n", str_replace([' owner ',' tech ',' admin ',' billing '], [' ',' ',' ',' '], $info['error']))))) : '';
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage().':'.$info, __LINE__, __FILE__);
			add_output('<div class="container alert alert-danger">'.$e->getMessage().':'.$info.'</div>');
			\StatisticClient::report('OpenSRS', $action, false, $e->getCode(), $e->getMessage(), STATISTICS_SERVER);
			return false;
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			\StatisticClient::report('OpenSRS', $action, false, $e->getCode(), $e->getMessage(), STATISTICS_SERVER);
			return false;
		}
		\StatisticClient::report('OpenSRS', $action, true, 0, '', STATISTICS_SERVER);
		return $osrsHandler;
	}

	/**
	 * performs an XML APi Request and grabs the response returning it
	 *
	 * @param string $action the
	 * @param string $object
	 * @param string $options
	 */
	public static function xmlRequest($action, $object, $options)
	{
		$username = OPENSRS_USERNAME;
		$privateKey = OPENSRS_KEY;
		$xml = '<?xml version="1.0" encoding="UTF-8" standalone="no" ?>
<!DOCTYPE OPS_envelope SYSTEM "ops.dtd">
<OPS_envelope>
	<header>
		<version>0.9</version>
	</header>
	<body>
		<data_block>
			<dt_assoc>
				<item key="protocol">XCP</item>
				<item key="action">'.$action.'</item>
				<item key="object">'.$object.'</item>
				<item key="attributes">
					<dt_assoc>';
		foreach ($options as $key => $value) {
			$xml .= PHP_EOL.'
						<item key="'.$key.'">'.$value.'</item>';
		}
		$xml .= '
					</dt_assoc>
				</item>
			</dt_assoc>
		</data_block>
	</body>
</OPS_envelope>';
		$signature = md5(md5($xml.$privateKey).$privateKey);
		$host = 'rr-n1-tor.opensrs.net';
		$port = 55443;
		$url = '/';
		$header = '';
		$header .= "post $url HTTP/1.0\r\n";
		$header .= "Content-Type: text/xml\r\n";
		$header .= 'X-Username: '.$username."\r\n";
		$header .= 'X-Signature: '.$signature."\r\n";
		$header .= 'Content-Length: '.mb_strlen($xml)."\r\n\r\n";
		// ssl:// requires OpenSSL to be installed
		$fp = fsockopen('ssl://'.$host, $port, $errno, $errstr, 30);
		if (!$fp) {
			myadmin_log(self::$module, 'debug', 'OpenSRS:'.$action.':'.$object.' Failed - Unknown Error '.$errno.' '.$errstr, __LINE__, __FILE__);
			return false;
		} else {
			$response = ['xml_str' => '','lines' => '','xml_obj' => '','xml_array' => ''];
			fputs($fp, $header.$xml);
			$i = 0;
			$response['xml_str'] = null;
			$response['lines'] = [];
			while (!feof($fp)) {
				$res = fgets($fp);
				$response['lines'][] = $res;
				if ($i >= 6) {
					$response['xml_str'] .= $res;
				}
				$i++;
			}
			fclose($fp);
			libxml_use_internal_errors(true);
			$response['xml_obj'] = simplexml_load_string($response['xml_str']); // Parse XML
			if (!$response['xml_obj']) {
				$errors = libxml_get_errors();
				foreach ($errors as $error) {
					myadmin_log('domains', 'error', 'This Line `'.$xml[$error->line - 1].'` gave a '.$levels[$error->level].' #'.$error->code.' `'.$error->message.'` at Line '.$error->line.' Column '.$error->column.' File '.$error->file, __LINE__, __FILE__, self::$module);
				}
				libxml_clear_errors();
				return false;
			} else {
				//$response['xml_array'] = xml2array($response['xml_str'], 1, 'attribute');
				$response['xml_array'] = json_decode(json_encode($response['xml_obj']), true); // Convert to array
			}
			return $response;
		}
	}

	/**
	 * Loads all the domain information into the 2 globals and sets the locked, whois_privacy, expiry_date, and registrarStatus globals based on the output.
	 * @return void
	 */
	public function loadDomainInfo()
	{
		$callstring = [
			'func' => 'lookupGetDomain',
			'attributes' => [
				//'cookie' => $this->cookie,
				'domain' => $this->serviceInfo['domain_hostname'],
				'type' => 'all_info',
				'bypass' => '',
				'registrant_ip' => '',
				'limit' => '1',
				'page' => '',
				'max_to_expiry' => '',
				'min_to_expiry' => ''
		]];
		$this->osrsHandlerAllInfo = self::request($callstring);
		$callstring = [
			'func' => 'lookupGetDomain',
			'attributes' => [
				//'cookie' => $this->cookie,
				'domain' => $this->serviceInfo['domain_hostname'],
				'type' => 'whois_privacy_state',
				'bypass' => '',
				'registrant_ip' => '',
				//'limit' => '10',
				'page' => '',
				'max_to_expiry' => '',
				'min_to_expiry' => ''
		]];
		$this->osrsHandlerWhoisPrivacy = self::request($callstring);
		$this->whoisPrivacy = $this->osrsHandlerWhoisPrivacy->resultFullRaw['attributes']['state'];
		$callstring = [
			'func' => 'lookupGetDomain',
			'attributes' => [
				//'cookie' => $this->cookie,
				'domain' => $this->serviceInfo['domain_hostname'],
				'type' => 'status',
				'bypass' => '',
				'registrant_ip' => '',
				//'limit' => '10',
				'page' => '',
				'max_to_expiry' => '',
				'min_to_expiry' => ''
		]];
		$this->osrsHandlerStatus = self::request($callstring);
		$this->locked = $this->osrsHandlerStatus->resultFullRaw['attributes']['lock_state'];
		$this->registrarStatus = $this->osrsHandlerAllInfo->resultFullRaw['attributes']['sponsoring_rsp'];
		$this->expiryDate = $this->osrsHandlerAllInfo->resultFullRaw['attributes']['expiredate'];
	}

	/**
	 * Gets an OpenSRS cookie for a domain name given the username and password for it. Can be called statically.
	 * @param string $username the username to authenticate with opensrs
	 * @param string $password the password for opensrs authentication
	 * @param string $domain the domain name
	 * @return false|string false if there was a problem or the string containing the cookie
	 */
	public static function getCookieRaw($username, $password, $domain)
	{
		$callstring = [
			'func' => 'cookieSet',
			'attributes' => [
				'reg_username' => $username,
				'reg_password' => $password,
				'domain' => $domain
		]];
		$osrsHandler = self::request($callstring);
		request_log('domains', false, __FUNCTION__, 'opensrs', 'cookieSet', $callstring, $osrsHandler);
		if (!isset($osrsHandler->resultFullRaw['attributes'])) {
			myadmin_log('domains', 'info', "Possible Problem with opensrs_Get_cookie({$username},{$password},{$domain}) - Returned ".json_encode($osrsHandler), __LINE__, __FILE__);
			return false;
		}
		$cookie = $osrsHandler->resultFullRaw['attributes']['cookie'];
		return $cookie;
	}

	/**
	 * Gets the current nameservers for a domain using an authenticated cookie. Can be called statically.
	 * @param string $cookie the cookie from opensrs_get_cookie()
	 * @return false|array false if error or an array of nameservers
	 */
	public static function getNameserversRaw($cookie)
	{
		$callstring = [
			'func' => 'nsGet',
			'attributes' => [
				'cookie' => $cookie,
				'name' => 'all'
		]];
		$osrsHandler = self::request($callstring);
		request_log('domains', false, __FUNCTION__, 'opensrs', 'nsGet', $callstring, $osrsHandler);
		return isset($osrsHandler->resultFullRaw['nameserver_list']) ? $osrsHandler->resultFullRaw['nameserver_list'] : false;
	}

	/**
	 * Creates a domain nameserver entry for a domain using an authenticated cookie. Can be called statically.
	 * @param string $cookie the cookie from opensrs_get_cookie() or domain name
	 * @param string $hostname hostname of the nameserver to add
	 * @param string $ip ip address of the nameserver to add
	 * @param bool $useDomain
	 * @return bool true if successful, false if there was an error.
	 */
	public static function createNameserverRaw($cookie, $hostname, $ip, $useDomain = false)
	{
		$callstring = [
			'func' => 'nsCreate',
			'attributes' => [
				$useDomain === false ? 'cookie' : 'domain' => $cookie,
				'name' => $hostname,
				'ipaddress' => $ip
		]];
		//echo "Call String: $callstring\n<br>";
		$osrsHandler = self::request($callstring);
		if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw) && $osrsHandler->resultFullRaw['is_success'] == 1) {
			// echo $osrsHandler->resultFullRaw['response_text'].'<br>';
			return true;
		} else {
			// echo 'ERROR: '.$osrsHandler->resultFullRaw['response_text'].'<br>';
			return false;
		}
		//echo '<pre>'; print_r($osrsHandler->resultFullRaw); echo '</pre>';
	}

	/**
	 * Deletes a domain nameserver entry for a domain using an authenticated cookie. Can be called statically.
	 * @param string $cookie the cookie from opensrs_get_cookie()
	 * @param string $hostname hostname of the nameserver to delete
	 * @param string $ip ip address of the nameserver to delete
	 * @param bool $useDomain
	 * @return bool true if successful, false if there was an error.
	 */
	public static function deleteNameserverRaw($cookie, $hostname, $ip, $useDomain = false)
	{
		$callstring = [
			'func' => 'nsDelete',
			'attributes' => [
				$useDomain === false ? 'cookie' : 'domain' => $cookie,
				'name' => $hostname,
				'ipaddress' => $ip
		]];
		//echo "Call String: $callstring\n<br>";
		$osrsHandler = self::request($callstring);
		request_log('domains', false, __FUNCTION__, 'opensrs', 'nsDelete', $callstring, $osrsHandler);
		myadmin_log('domains', 'info', 'Delete NS Response'.json_encode($osrsHandler), __LINE__, __FILE__);
		if ($osrsHandler->resultFullRaw['is_success'] == 1) {
			//			echo $osrsHandler->resultFullRaw['response_text'].'<br>';
			return true;
		} else {
			//			echo 'ERROR: '.$osrsHandler->resultFullRaw['response_text'].'<br>';
			return false;
		}
		//echo '<pre>'; print_r($osrsHandler->resultFullRaw); echo '</pre>';
	}

	/**
	 * Checks to see if the specified domain can be transferred in to OpenSRS, or
	 * from one OpenSRS Reseller to another. This call can also be used to check
	 * the status of the last transfer request on a given domain name.
	 * When you use the check_transfer action prior to initiating a transfer, the
	 * transferable response parameter is most relevant, and if transferable = 0 ,
	 * the reason field is also important.
	 * When you use the check_transfer action to determine the progress of a
	 * transfer, the status parameter is most important. If the response indicates
	 * that the transfer is in progress and the status is pending_registry , the
	 * transfer will be scheduled to complete within 5 minutes of the query; running
	 * the query expedites the process and causes the transfer to complete within 5
	 * minutes.
	 *
	 * @param string $domain the domain name to check transfer status of
	 * @param int $checkStatus Flag to request the status of a transfer request. If the transfer state is returned as pending_registry and the Registry shows OpenSRS as the Registrar of record, OpenSRS schedules the completion of gTLD transfers. Allowed values are 0 or 1.
	 * @param int $getRequestAddress Flag to request the registrant's contact email address. This is useful if you want to make sure that your client can receive mail at that address to acknowledge the transfer. Allowed values are 0 or 1.
	 * @return array|bool
	 */
	public static function transferCheck($domain, $checkStatus = 0, $getRequestAddress = 0)
	{
		$callstring = [
			'func' => 'transCheck',
			'attributes' => [
				'domain' => $domain,
				'check_status' => $checkStatus,
				'get_request_address' => $getRequestAddress
		]];
		$osrsHandler = self::request($callstring);
		if ($osrsHandler === false) {
			return false;
		}
		return $osrsHandler->resultFullRaw;
	}

	/**
	 * Queries various types of data regarding the user's domain. For example, the
	 * all_info type allows you to retrieve all data for the domain linked to the
	 * current cookie. The list type queries the list of domains associated with the
	 * user's profile. The list type can also be used to return a list of domains that
	 * expire within a specified range. The nameservers type returns the
	 * nameservers currently acting as DNS servers for the domain.
	 *
	 * @param string $domain the domain name to lookup	 *
	 * @param string $type Type of query. Allowed values are: 'admin—Returns' - admin contact information.	  'all_info' - Returns all information. 'auto_renew_flag' - Deprecated, Returned list of domains. 'billing' - Returns billing contact information. 'ca_whois_display_setting' - Returns the current CIRA Whois Privacy setting for .CA domains. 'domain_auth_info' ' - Returns domain authorization code, if applicable. 'expire_action' Returns the action to be taken upon domain expiry, specifically whether to auto-renew the domain, or let it expire silently. 'forwarding_email' - Returns forwarding email for .NAME 2nd level. 'it_whois_display_setting' - Returns the current Whois Privacy setting for .IT domains. 'list' - Returns list of domains for user. 'nameservers' - Returns nameserver information. 'owner' - Returns owner contact information. 'rsp_whois_info' - Returns name and contact information for RSP. 'status' - Returns lock or escrow status of the domain. 'tech' - Returns tech contact information. 'tld_data' - Returns additional information that is required by some registries, such as the residency of the registrant. 'trademark' - Deprecated. Used for .CA domains; returns 'Y' or 'N' value indicating whether the registered owner of the domain name is the legal holder of the trademark for that word. 'waiting history' - Returns information on asynchronous requests. 'whois_privacy_state ' - Returns the state for the WHOIS Privacy feature: enabled, disabled, enabling, or disabling. Note: If the TLD does not allow WHOIS Privacy, always returns Disabled. 'xpack_waiting_history' - Returns the state of completed/cancelled requests not yet deleted from the database for .DK domains. All completed/cancelled requests are deleted from the database two
	 * @return array|bool
	 */
	public static function lookupGetDomain($domain, $type = 'all_info')
	{
		$callstring = [
			'func' => 'lookupGetDomain',
			'attributes' => [
				'domain' => $domain,
				'type' => $type,
				'bypass' => '',
				'registrant_ip' => '',
				//'limit' => '10',
				'page' => '',
				'max_to_expiry' => '',
				'min_to_expiry' => ''
		]];
		$osrsHandler = self::request($callstring);
		if ($osrsHandler === false) {
			return false;
		}
		return $osrsHandler->resultFullRaw;
	}

	/**
	 * Does a lookup on the domain and returns an array of information about it. Can be called statically.
	 *
	 * @param string $domain the domain name to lookup
	 * @param bool|string $selected false to not use this, tld to use just the tld from the domain, or available to use aall availalbe tlds
	 * @return array|bool
	 */
	public static function lookupDomain($domain, $selected = false)
	{
		$callarray = [
			'func' => 'lookupDomain',
			'attributes' => [
				'domain' => $domain
		]];
		if ($selected == 'tld') {
			$callarray['attributes']['selected'] = get_domain_tld($domain);
		} elseif ($selected == 'available') {
			$callarray['attributes']['selected'] = implode(';', get_available_domain_tlds());
		}
		$callstring = $callarray;
		$osrsHandler = self::request($callstring);
		if ($osrsHandler === false) {
			return false;
		}
		return $osrsHandler->resultFullRaw;
	}

	/**
	 * Checks whether or not the domain is available for registration. Can be called statically.
	 * @param string $domain the domain name to lookup
	 * @return bool returns true if the domain is available, false otherwise
	 */
	public static function checkDomainAvailable($domain)
	{
		$result = self::lookupDomain($domain);
		if ($result === false) {
			return false;
		} elseif (isset($result['attributes']['status'])) {
			return ($result['attributes']['status'] == 'available' ? true : false);
		} else {
			$resultValues = array_values($result);
		}
		foreach ($resultValues as $data) {
			if (isset($data['domain']) && $data['domain'] == $domain) {
				if ($data['status'] == 'available') {
					return true;
				} else {
					return false;
				}
			}
		}
		return false;
	}

	/**
	 * Checks the OpenSRS price for a domain name. Can be called statically.
	 *
	 * @param string $domain the domain name to lookup the price for
	 * @param string $regType registration type, defaults to 'new', can be new, transfer, or renew
	 * @return false|float false if there was an error or the price
	 */
	public static function lookupDomainPrice($domain, $regType = 'new')
	{
		$callstring = [
			//'func' => 'allinoneDomain',
			//'func' => 'PremiumDomain',
			//'func' => 'SuggestDomain',
			'func' => 'lookupGetPrice',
			'attributes' => [
				//'searchstring' => $domain,
				'domain' => $domain,
				'reg_type' => $regType,
				//'tlds' => array_keys(get_available_domain_tlds_by_tld()),
				//'tlds' => array(get_domain_tld($domain)),
		]];
		$osrsHandler = self::request($callstring);
		//myadmin_log('domains', 'info', json_encode($osrsHandler->resultFullRaw), __LINE__, __FILE__);
		$resultValues = array_values($osrsHandler->resultFullRaw);
		foreach ($resultValues as $data) {
			if (isset($data['domain']) && $data['domain'] == $domain) {
				return $data['price'];
			}
		}
		return false;
	}

	/**
	 * Searches for a domain name matching it up with our tlds that we have buyable. Can be called statically.
	 *
	 * @param string $domain the domain name or part to search for
	 * @param string $function the search function to perform
	 * @return array|bool
	 */
	public static function searchDomain($domain, $function)
	{
		$final = [];
		$tlds = get_available_domain_tlds_by_tld();
		$tldPrices = get_service_tld_pricing();
		if (in_array($function, ['allinoneDomain'])) {
			myadmin_log('domains', 'error', "searchDomain call passed obsolete function $function, use SuggestDomain instead.", __LINE__, __FILE__);
		}
		if (in_array($function, ['allinoneDomain', 'SuggestDomain'])) {
			$callstring = [
				'func' => $function,
				'attributes' => [
					'searchstring' => $domain, // These are optional
					'tlds' => array_keys($tlds)
			]];
		} else {
			$callstring = [
				'func' => $function,
				'attributes' => [
					'domain' => $domain, // These are optional
					'selected' => implode(';', array_keys($tlds)),
					'alldomains' => implode(';', array_keys($tlds))
			]];
		}
		$osrsHandler = self::request($callstring);
		if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
			$resultTypes = array_keys($osrsHandler->resultFullRaw['attributes']);
			foreach ($resultTypes as $resultType) {
				if (isset($osrsHandler->resultFullRaw['attributes'][$resultType]['items'])) {
					foreach ($osrsHandler->resultFullRaw['attributes'][$resultType]['items'] as $idx => $data) {
						if (isset($data['domain'])) {
							$tld = get_domain_tld($data['domain']);
							if ($tld != '') {
								if ($tld[0] != '.') {
									$tld = '.'.$tld;
								}
								if (isset($tlds[$tld])) {
									$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['id'] = $tlds[$tld]['id'];
									$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['tld'] = $tld;
									$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['cost'] = $tlds[$tld]['cost'];
									if (isset($tldPrices[$tld]['new'])) {
										$diff = bcsub($tlds[$tld]['cost'], $tldPrices[$tld]['new'], 2);
										if (isset($tldPrices[$tld]['new'])) {
											$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['new'] = bcadd($tldPrices[$tld]['new'], $diff, 2);
										}
										if (isset($tldPrices[$tld]['renewal'])) {
											$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['renewal'] = bcadd($tldPrices[$tld]['renewal'], $diff, 2);
										}
										if (isset($tldPrices[$tld]['transfer'])) {
											$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['transfer'] = bcadd($tldPrices[$tld]['transfer'], $diff, 2);
										}
									}
								} else {
									myadmin_log('domains', 'info', "tld $tld was not set", __LINE__, __FILE__);
								}
							} else {
								myadmin_log('domains', 'info', "domain {$data['domain']} got blank TLD response {$tld}", __LINE__, __FILE__);
							}
						}
					}
				}
			}
			$final['domainData'] = $osrsHandler->resultFullRaw;
		}
		$final['tlds'] = $tlds;
		//$final['tldPrices'] = $tldPrices;
		return $final;
	}

	/**
	 * @param      $domain
	 * @param bool $lock
	 * @return bool
	 */
	public static function lock($domain, $lock = true)
	{
		$lockStatusUpdate = $lock === true ? 1 : 0;
		$response = self::xmlRequest('modify', 'domain', ['domain_name'=>$domain,'lock_state'=>$lockStatusUpdate,'data'=>'status']);
		if ($response === false || !$response['lines'][20]) {
			return false;
		}
		return true;
	}

	/**
	 * Enable/Disable the whois privacy for the given domain. Can be called statically.
	 *
	 * @param string $domain  the domain name to set status on
	 * @param bool   $enabled true if privacy status should be on, false if not
	 * @return bool
	 */
	public static function whoisPrivacy($domain, $enabled)
	{
		$privacyStatusUpdate = $enabled == true ? 'enable' : 'disable';
		$response = self::xmlRequest('modify', 'domain', ['domain_name'=>$domain,'state'=>$privacyStatusUpdate,'data'=>'whois_privacy_state']);
		if ($response === false) {
			return false;
		}
		$result2 = trim(strip_tags($response['lines'][17]));
		myadmin_log('domains', 'info', "OpenSRS::whoisPrivacy({$domain}, {$privacyStatusUpdate}) returned {$result2}", __LINE__, __FILE__);
		return true;
	}

	/**
	 * gets an array of domains registered by expiry date
	 *
	 * @param bool|false|string $startDate start date for lookups, or false(default) for now + 45 days
	 * @param bool|false|string $endDate   end date for lookups, or false(default) or 12-31 in 20 years
	 * @return array array of domains in the format of domain => expire date
	 */
	public static function listDomainsByExpireyDate($startDate = false, $endDate = false)
	{
		if ($startDate == false) {
			$startDate = date('Y-m-d', strtotime(date('Y').'-01-01 +45 days'));
		}
		if ($endDate == false) {
			$endDate = date('Y-m-d', strtotime(date('Y').'-12-31 +20 years'));
		}
		$fromDate = date('Y-m-d', strtotime($startDate));
		$toDate = date('Y-m-d', strtotime($endDate));
		$limit = 99999;
		$page = 0;
		$endPages = false;
		$domains = [];
		while ($endPages == false) {
			$page++;
			$response = self::xmlRequest('get_domains_by_expiredate', 'domain', ['limit'=>$limit,'exp_from'=>$fromDate,'exp_to'=>$toDate,'page'=>$page]);
			if ($response === false) {
				$endPages = true;
			} else {
				if (!isset($response['xml_array']['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item']) || !is_array($response['xml_array']['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item'])) {
					myadmin_log('domains', 'warning', __NAMESPACE__.'::'.__METHOD__.' returned '.json_encode($response['xml_array']), __LINE__, __FILE__);
					$endPages = true;
				} else {
					$domainArray = $response['xml_array']['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item'];
					$domainValues = array_values($response['xml_array']['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item']);
					foreach ($domainValues as $domainData) {
						$domains[$domainData['dt_assoc']['item'][1]] = $domainData['dt_assoc']['item'][2];
					}
					if (sizeof($domainArray) < $limit) {
						$endPages = true;
					}
				}
			}
		}
		return $domains;
	}

	/**
	 * Removes the domain at registry level
	 *
	 * @param string $domain the domain name or part to search for
	 * @return array returns true if domain cancelled else false
	 */
	public static function redeemDomain($domain)
	{
		$response = self::xmlRequest('REDEEM', 'DOMAIN', ['domain'=>$domain]);
		if ($response === false) {
			return false;
		}
		$resultArray = $response['xml_array']['body']['data_block']['dt_assoc'];
		myadmin_log('domains', 'info', "OpenSRS::redeemDomain({$domain}) returned {$resultArray}", __LINE__, __FILE__);
		return $resultArray;
	}

	public static function ackEvent($event_id)
	{
		$response = self::xmlRequest('EVENT', 'ACK', ['event_id'=>$event_id]);
		if ($response === false) {
			return false;
		}
		//$response['xml_array'] = xml2array($response['xml_str'], 1, 'attribute');
		$response['xml_array'] = $response['xml_array']['OPS_envelope']['body']['data_block']['dt_assoc']['item'];
		$resultArray = self::response_to_array($response['xml_array']);
		myadmin_log('domains', 'info', "OpenSRS::ackEvent({$event_id}) returned ".json_encode($resultArray), __LINE__, __FILE__);
		return $resultArray['is_success'] == '1';
	}


	/**
	 * Polls the OpenSRS server for a new Event
	 *
	 * @link https://help.opensrs.com/hc/en-us/articles/203245923-Event-Notifications
	 * @param int $limit optional limit, defaults to 1 (which is also the recomended amount to process at once), allows for polling multiple (up to 100 maximum) events at a time
	 * @return array returns some crap
	 */
	public static function pollEvent($limit = 1)
	{
		$response = self::xmlRequest('EVENT', 'POLL', ['limit'=>$limit]);
		if ($response['xml_str'] !== false) {
			//function_requirements('xml2array');
			//$response['xml_array'] = xml2array($response['xml_str'], 1, 'attribute');
			$response['xml_array'] = $response['xml_array']['OPS_envelope']['body']['data_block']['dt_assoc']['item'];
			$resultArray = self::response_to_array($response['xml_array']);
			myadmin_log('domains', 'info', "OpenSRS::pollEvent({$limit}) returned ".json_encode($resultArray), __LINE__, __FILE__);
			if ($resultArray['is_success'] == '1') {
				if (self::ackEvent($resultArray['attributes']['events']['event_id'])) {
					// Add code here to log and process the poll event
				}
			}
			return $resultArray;
		}
	}
}
