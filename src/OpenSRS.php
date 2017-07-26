<?php
/**
 * OpenSRS Domain Related Functionality
 * Last Changed: $LastChangedDate: 2016-08-22 08:39:20 -0400 (Mon, 22 Aug 2016) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Domains
 */
namespace Detain\MyAdminOpenSRS;

require_once __DIR__.'/openSRS_loader.php';
use opensrs\Request;

/**
 * OpenSRS Domain Class
 * @access public
 */
class OpenSRS {
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

	/**
	 * OpenSRS::OpenSRS()
	 *
	 * @param bool $id
	 * @internal param string $username the API username
	 * @internal param string $password the API password
	 * @internal param bool $testing optional (defaults to false) testing
	 */
	public function __construct($id = FALSE) {
		$this->settings = get_module_settings($this->module);
		if ($id != FALSE)
			$this->id = (int) $id;
		elseif (isset($GLOBALS['tf']->variables->request['id']))
			$this->id = (int) $GLOBALS['tf']->variables->request['id'];
		else
			return;
		$this->serviceInfo = get_service($this->id, $this->module);
		if ($this->serviceInfo === FALSE)
			return;
		$this->serviceExtra = run_event('parse_service_extra', $this->serviceInfo[$this->settings['PREFIX'].'_extra'], $this->module);
		$this->serviceAddons = get_service_addons($this->id, $this->module);
		$this->cookie = $this->getCookieRaw($this->serviceInfo['domain_username'], $this->serviceInfo['domain_password'], $this->serviceInfo['domain_hostname']);
		$this->loadDomainInfo();
	}

	/**
	 * Loads all the domain information into the 2 globals and sets the locked, whois_privacy, expiry_date, and registrarStatus globals based on the output.
	 * @return void
	 */
	public function loadDomainInfo() {
		$callstring = json_encode(
			[
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
			]
			]
		);
		try {
			$request = new Request();
			$this->osrsHandlerAllInfo = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
		$callstring = json_encode(
			[
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
			]
			]
		);
		try {
			$request = new Request();
			$this->osrsHandlerWhoisPrivacy = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
		$this->whoisPrivacy = $this->osrsHandlerWhoisPrivacy->resultFullRaw['attributes']['state'];
		$callstring = json_encode(
			[
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
			]
			]
		);
		try {
			$request = new Request();
			$this->osrsHandlerStatus = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
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
	public static function getCookieRaw($username, $password, $domain) {
		$callstring = json_encode(
			[
			'func' => 'cookieSet',
			'attributes' => [
				'reg_username' => $username,
				'reg_password' => $password,
				'domain' => $domain
			]
			]
		);
		try {
			$request = new Request();
			$osrsHandler = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
		request_log('domains', FALSE, __FUNCTION__, 'opensrs', 'cookieSet', $callstring, $osrsHandler);
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
	public static function getNameserversRaw($cookie) {
		$callstring = json_encode(
			[
			'func' => 'nsGet',
			'attributes' => [
				'cookie' => $cookie,
				'name' => 'all'
			]
			]
		);
		try {
			$request = new Request();
			$osrsHandler2 = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
		request_log('domains', FALSE, __FUNCTION__, 'opensrs', 'nsGet', $callstring, $osrsHandler2);
		if (isset($osrsHandler2->resultFullRaw['nameserver_list'])) {
			return $osrsHandler2->resultFullRaw['nameserver_list'];
		} else {
			return false;
		}
	}

	/**
	 * Creates a domain nameserver entry for a domain using an authenticated cookie. Can be called statically.
	 * @param string $cookie the cookie from opensrs_get_cookie() or domain name
	 * @param string $hostname hostname of the nameserver to add
	 * @param string $ip ip address of the nameserver to add
	 * @param bool $useDomain
	 * @return bool true if successful, false if there was an error.
	 */
	public static function createNameserverRaw($cookie, $hostname, $ip, $useDomain = FALSE) {
		$callstring = json_encode(
			[
			'func' => 'nsCreate',
			'attributes' => [
				$useDomain === FALSE ? 'cookie' : 'domain' => $cookie,
				'name'                                      => $hostname,
				'ipaddress'                                 => $ip
			]
			]
		);
		//echo "Call String: $callstring\n<br>";
		try {
			$request = new Request();
			$osrsHandler2 = $request->process('json', $callstring);
			request_log('domains', FALSE, __FUNCTION__, 'opensrs', 'nsCreate', $callstring, $osrsHandler2);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
		if (isset($osrsHandler2) && isset($osrsHandler2->resultFullRaw) && $osrsHandler2->resultFullRaw['is_success'] == 1) {
			//			echo $osrsHandler2->resultFullRaw['response_text'].'<br>';
			return true;
		} else {
			//			echo 'ERROR: '.$osrsHandler2->resultFullRaw['response_text'].'<br>';
			return false;

		}
		//echo '<pre>'; print_r($osrsHandler2->resultFullRaw); echo '</pre>';
	}

	/**
	 * Deletes a domain nameserver entry for a domain using an authenticated cookie. Can be called statically.
	 * @param string $cookie the cookie from opensrs_get_cookie()
	 * @param string $hostname hostname of the nameserver to delete
	 * @param string $ip ip address of the nameserver to delete
	 * @param bool $useDomain
	 * @return bool true if successful, false if there was an error.
	 */
	public static function deleteNameserverRaw($cookie, $hostname, $ip, $useDomain = FALSE) {
		$callstring = json_encode(
			[
			'func' => 'nsDelete',
			'attributes' => [
				$useDomain === FALSE ? 'cookie' : 'domain' => $cookie,
				'name'                                      => $hostname,
				'ipaddress'                                 => $ip
			]
			]
		);
		//echo "Call String: $callstring\n<br>";
		try {
			$request = new Request();
			$osrsHandler2 = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
		request_log('domains', FALSE, __FUNCTION__, 'opensrs', 'nsDelete', $callstring, $osrsHandler2);
		myadmin_log('domains', 'info', 'Delete NS Response'.json_encode($osrsHandler2), __LINE__, __FILE__);
		if ($osrsHandler2->resultFullRaw['is_success'] == 1) {
			//			echo $osrsHandler2->resultFullRaw['response_text'].'<br>';
			return true;
		} else {
			//			echo 'ERROR: '.$osrsHandler2->resultFullRaw['response_text'].'<br>';
			return false;

		}
		//echo '<pre>'; print_r($osrsHandler2->resultFullRaw); echo '</pre>';
	}

	/**
	 * Checks to see if the specified domain can be transferred in to OpenSRS, or
	 * from one OpenSRS Reseller to another. This call can also be used to check
	 * the status of the last transfer request on a given domain name.
	 *
	 * When you use the check_transfer action prior to initiating a transfer, the
	 * transferable response parameter is most relevant, and if transferable = 0 ,
	 * the reason field is also important.
	 *
	 * When you use the check_transfer action to determine the progress of a
	 * transfer, the status parameter is most important. If the response indicates
	 * that the transfer is in progress and the status is pending_registry , the
	 * transfer will be scheduled to complete within 5 minutes of the query; running
	 * the query expedites the process and causes the transfer to complete within 5
	 * minutes.
	 *
	 * @param string $domain the domain name to check transfer status of
	 * @param int $check_status Flag to request the status of a transfer request. If the transfer state is returned as pending_registry and the Registry shows OpenSRS as the Registrar of record, OpenSRS schedules the completion of gTLD transfers. Allowed values are 0 or 1.
	 * @param int $getRequestAddress Flag to request the registrant's contact email address. This is useful if you want to make sure that your client can receive mail at that address to acknowledge the transfer. Allowed values are 0 or 1.
	 * @return array an array of result information.
	 */
	public static function transfer_check($domain, $checkStatus = 0, $getRequestAddress = 0) {
		$callstring = json_encode(
			[
			'func' => 'transCheck',
			'attributes' => [
				'domain' => $domain,
				'check_status' => $checkStatus,
				'get_request_address' => $getRequestAddress
			]
			]
		);
		try {
			$request = new Request();
			$osrsHandler = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			return false;
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
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
	 * @return array an array of result information.
	 */
	public static function lookup_get_domain($domain, $type = 'all') {
		$callstring = json_encode(
			[
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
			]
			]
		);
		try {
			$request = new Request();
			$osrsHandler = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			return false;
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			return false;
		}
		return $osrsHandler->resultFullRaw;
	}



	/**
	 * Does a lookup on the domain and returns an array of information about it. Can be called statically.
	 * @param string $domain the domain name to lookup
	 * @param bool|string $selected false to not use this, tld to use just the tld from the domain, or available to use aall availalbe tlds
	 * @return array an array of result information.
	 */
	public static function lookup_domain($domain, $selected = FALSE) {
		//myadmin_log('domains', 'info', "Checking if domain $domain available", __LINE__, __FILE__);
		// Put the data to the Formatted array
		$callarray = [
			'func' => 'lookupDomain',
			'attributes' => [
				'domain' => $domain
			]
		];
		if ($selected == 'tld') {
			$callarray['attributes']['selected'] = get_domain_tld($domain);
		} elseif ($selected == 'available') {
			$callarray['attributes']['selected'] = implode(';', get_available_domain_tlds());
		}

		$callstring = json_encode($callarray);
		try {
			$request = new Request();
			$osrsHandler = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			return false;
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			return false;
		}
		return $osrsHandler->resultFullRaw;
	}

	/**
	 * Checks whether or not the domain is available for registration. Can be called statically.
	 * @param string $domain the domain name to lookup
	 * @return bool returns true if the domain is available, false otherwise
	 */
	public static function check_domain_available($domain) {
		$result = OpenSRS::lookup_domain($domain);
		if (isset($result['attributes']['status']))
			return ($result['attributes']['status'] == 'available' ? true : false);
		else
			$resultValues = array_values($result);
			foreach ($resultValues as $data)
				if (isset($data['domain']) && $data['domain'] == $domain)
					if ($data['status'] == 'available')
						return true;
					else
						return false;
		return false;
	}

	/**
	 * Checks the OpenSRS price for a domain name. Can be called statically.
	 *
	 * @param string $domain the domain name to lookup the price for
	 * @param string $regType registration type, defaults to 'new', can be new, transfer, or renew
	 * @return false|float false if there was an error or the price
	 */
	public static function lookup_domain_price($domain, $regType = 'new') {
		// Put the data to the Formatted array
		$callstring = json_encode(
			[
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
			]
			]
		);
		try {
			$request = new Request();
			$osrsHandler = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
		}
		//myadmin_log('domains', 'info', json_encode($osrsHandler->resultFullRaw), __LINE__, __FILE__);
		$resultValues = array_values($osrsHandler->resultFullRaw);
		foreach ($resultValues as $data)
			if (isset($data['domain']) && $data['domain'] == $domain)
				return $data['price'];
		return false;
	}

	/**
	 * Searches for a domain name matching it up with our tlds that we have buyable. Can be called statically.
	 *
	 * @param string $domain the domain name or part to search for
	 * @param string $function the search function to perform
	 * @return array returns an array containing the search results
	 */
	public static function search_domain($domain, $function) {
		$final = [];
		$tlds = get_available_domain_tlds_by_tld();
		$tldPrices = get_service_tld_pricing();
		if (in_array($function, ['allinoneDomain']))
			myadmin_log('domains', 'error', "search_domain call passed obsolete function $function, use SuggestDomain instead.", __LINE__, __FILE__);
		if (in_array($function, ['allinoneDomain', 'SuggestDomain']))
			$callstring = json_encode(
				[
				'func' => $function,
				'attributes' => [
					'searchstring' => $domain, // These are optional
					'tlds' => array_keys($tlds)
				]
				]
			);
		else
			$callstring = json_encode(
				[
				'func' => $function,
				'attributes' => [
					'domain' => $domain, // These are optional
					'selected' => implode(';', array_keys($tlds)),
					'alldomains' => implode(';', array_keys($tlds))
				]
				]
			);
		try {
			$request = new Request();
			$osrsHandler = $request->process('json', $callstring);
		} catch (\opensrs\APIException $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			return false;
		} catch (\opensrs\Exception $e) {
			myadmin_log('opensrs', 'error', $callstring.':'.$e->getMessage(), __LINE__, __FILE__);
			return false;
		}
		//request_log('domains', FALSE, __FUNCTION__, 'opensrs', $function, $callstring, $osrsHandler);
		//echo '<pre>';print_r($tlds);echo '</pre>';exit;
		//echo '<pre>';var_dump($osrsHandler);echo '</pre>';exit;
		//	echo (" In: ". $callstring ."<br><br><br><br>");
		//	echo ("Out: ". $osrsHandler->resultFormatted);
		if (isset($osrsHandler) && isset($osrsHandler->resultFullRaw)) {
			$resultTypes = array_keys($osrsHandler->resultFullRaw['attributes']);
			foreach ($resultTypes as $resultType)
				if (isset($osrsHandler->resultFullRaw['attributes'][$resultType]['items']))
					foreach ($osrsHandler->resultFullRaw['attributes'][$resultType]['items'] as $idx => $data)
						if (isset($data['domain'])) {
							$tld = get_domain_tld($data['domain']);
							if ($tld != '') {
								if ($tld[0] != '.')
									$tld = '.'.$tld;
								if (isset($tlds[$tld])) {
									$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['id'] = $tlds[$tld]['id'];
									$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['tld'] = $tld;
									$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['cost'] = $tlds[$tld]['cost'];
									if (isset($tldPrices[$tld]['new'])) {
										$diff = bcsub($tlds[$tld]['cost'], $tldPrices[$tld]['new'], 2);
										if (isset($tldPrices[$tld]['new']))
											$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['new'] = bcadd($tldPrices[$tld]['new'], $diff, 2);
										if (isset($tldPrices[$tld]['renewal']))
											$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['renewal'] = bcadd($tldPrices[$tld]['renewal'], $diff, 2);
										if (isset($tldPrices[$tld]['transfer']))
											$osrsHandler->resultFullRaw['attributes'][$resultType]['items'][$idx]['transfer'] = bcadd($tldPrices[$tld]['transfer'], $diff, 2);
									}
								} else
									myadmin_log('domains', 'info', "TLD $tld was not set", __LINE__, __FILE__);
							} else
								myadmin_log('domains', 'info', "domain {$data['domain']} got blank TLD response {$tld}", __LINE__, __FILE__);
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
	public static function lock($domain, $lock = TRUE) {
		if ($lock === TRUE)
			$lockStatusUpdate = 1;
		else
			$lockStatusUpdate = 0;
		$username = OPENSRS_USERNAME;
		$privateKey = OPENSRS_KEY;
		$xml = '<?xml version=\'1.0\' encoding="UTF-8" standalone="no" ?>
			<!DOCTYPE OPS_envelope SYSTEM "ops.dtd">
			<OPS_envelope>
				<header>
					<version>XML:0.1</version>
				</header>
				<body>
					<data_block>
						<dt_assoc>
							<item key="protocol">XCP</item>
							<item key="action">modify</item>
							<item key="object">domain</item>
							<item key="attributes">
								<dt_assoc>
									<item key="domain_name">'.$domain.'</item>
									<item key="lock_state">'.$lockStatusUpdate.'</item>
`											<item key="data">status</item>
							</dt_assoc>
						</item>
					</dt_assoc>
				</data_block>
			</body>
			</OPS_envelope>
		';
		$signature = md5(md5($xml.$privateKey).$privateKey);
		$host = 'rr-n1-tor.opensrs.net';
		$port = 55443;
		$url = '/';
		$header = '';
		$header .= "POST $url HTTP/1.0\r\n";
		$header .= "Content-Type: text/xml\r\n";
		$header .= 'X-Username: '.$username."\r\n";
		$header .= 'X-Signature: '.$signature."\r\n";
		$header .= 'Content-Length: '.mb_strlen($xml)."\r\n\r\n";
		// ssl:// requires OpenSSL to be installed
		$fp = fsockopen("ssl://$host", $port, $errno, $errstr, 30);
		if (!$fp) {
			myadmin_log(self::$module, 'debug', 'OpenSRS Failed - Unknown Error '.$errno.' '.$errstr, __LINE__, __FILE__);
			return false;
		} else {
			// post the data to the server
			fputs($fp, $header.$xml);
			while (!feof($fp)) {
				$res = fgets($fp, 1024);
				$line[] = $res;
			}
			fclose($fp);
			if (!$line[20]) {
				myadmin_log(self::$module, 'debug', 'OpenSRS Failed - '.$line[17], __LINE__, __FILE__);
				return false;
			}
		}
		return true;
	}

	/**
	 * Enable/Disable the whois privacy for the given domain. Can be called statically.
	 * @param string $domain the domain name to set status on
	 * @param bool $enabled true if privacy status should be on, false if not
	 */
	public static function whois_privacy($domain, $enabled) {
		if ($enabled == TRUE)
			$privacyStatusUpdate = 'enable';
		else
			$privacyStatusUpdate = 'disable';
		$username = OPENSRS_USERNAME;
		$privateKey = OPENSRS_KEY;
		$xml = '<?xml version=\'1.0\' encoding="UTF-8" standalone="no" ?>
<!DOCTYPE OPS_envelope SYSTEM "ops.dtd">
<OPS_envelope>
	<header>
		<version>XML:0.1</version>
	</header>
	<body>
		<data_block>
			<dt_assoc>
				<item key="protocol">XCP</item>
				<item key="action">modify</item>
				<item key="object">domain</item>
				<item key="attributes">
					<dt_assoc>
						<item key="domain_name">'.$domain.'</item>
						<item key="state">'.$privacyStatusUpdate.'</item>
						<item key="data">whois_privacy_state</item>
					</dt_assoc>
				</item>
			</dt_assoc>
		</data_block>
	</body>
</OPS_envelope>';
		$signature = md5(md5($xml.$privateKey).$privateKey);
		$prefix = 'ssl://';
		$host = 'rr-n1-tor.opensrs.net';
		$port = 55443;
		$url = '/';
		$header = '';
		$header .= "POST $url HTTP/1.0\r\n";
		$header .= "Content-Type: text/xml\r\n";
		$header .= 'X-Username: '.$username."\r\n";
		$header .= 'X-Signature: '.$signature."\r\n";
		$header .= 'Content-Length: '.mb_strlen($xml)."\r\n\r\n";
		// ssl:// requires OpenSSL to be installed
		$fp = fsockopen($prefix.$host, $port, $errno, $errstr, 30);
		if (!$fp) {
			return false;
			myadmin_log('domains', 'info', "OpenSRS::whois_privacy({$domain}, {$privacyStatusUpdate}) returned error {$errno} {$errstr} on fsockopen", __LINE__, __FILE__);
		} else {
			// post the data to the server
			fputs($fp, $header.$xml);
			while (!feof($fp)) {
				$res = fgets($fp, 1024);
				$line[] = $res;
			}
			fclose($fp);
			if ($line[20])
				$result2 = $line[17];
			else
				$result2 = $line[17];
			$result2 = trim(strip_tags($result2));
			myadmin_log('domains', 'info', "OpenSRS::whois_privacy({$domain}, {$privacyStatusUpdate}) returned {$result2}", __LINE__, __FILE__);
		}
		return true;
	}

	/**
	 * gets an array of domains registered by expiry date
	 *
	 * @param bool|false|string $startDate start date for lookups, or false(default) for now + 45 days
	 * @param bool|false|string $endDate   end date for lookups, or false(default) or 12-31 in 20 years
	 * @return array array of domains in the format of domain => expire date
	 */
	public static function list_domains_by_expirey_date($startDate = FALSE, $endDate = FALSE) {
		if ($startDate == FALSE)
			$startDate = date('Y-m-d', strtotime(date('Y').'-01-01 +45 days'));
		if ($endDate == FALSE)
			$endDate = date('Y-m-d', strtotime(date('Y').'-12-31 +20 years'));
		$fromDate = date('Y-m-d', strtotime($startDate));
		$toDate = date('Y-m-d', strtotime($endDate));
		$limit = 99999;
		$page = 0;
		$endPages = FALSE;
		$domains = [];
		while ($endPages == FALSE) {
			$page++;
			$xml = '<?xml version=\'1.0\' encoding=\'UTF-8\' standalone=\'no\' ?>
<!DOCTYPE OPS_envelope SYSTEM \'ops.dtd\'>
<OPS_envelope>
	<header>
		<version>0.9</version>
	</header>
<body>
	<data_block>
		<dt_assoc>
			<item key="protocol">XCP</item>
			<item key="action">get_domains_by_expiredate</item>
			<item key="object">domain</item>
			<item key="attributes">
				<dt_assoc>
					<item key="limit">'.$limit.'</item>
					<item key="exp_from">'.$fromDate.'</item>
					<item key="exp_to">'.$toDate.'</item>
					<item key="page">'.$page.'</item>
				</dt_assoc>
			</item>
		</dt_assoc>
	</data_block>
</body>
</OPS_envelope>';
			$signature = md5(md5($xml.OPENSRS_KEY).OPENSRS_KEY);
			$host = 'rr-n1-tor.opensrs.net';
			$port = 55443;
			$url = '/';
			$header = '';
			$header .= "POST $url HTTP/1.0\r\n";
			$header .= "Content-Type: text/xml\r\n";
			$header .= 'X-Username: '.OPENSRS_USERNAME."\r\n";
			$header .= 'X-Signature: '.$signature."\r\n";
			$header .= 'Content-Length: '.mb_strlen($xml)."\r\n\r\n";
			$fp = fsockopen("ssl://$host", $port, $errno, $errstr, 30);
			if (!$fp) {
				myadmin_log('domains', 'info', "OpenSRS::".__FUNCTION__." returned error {$errno} {$errstr} on fsockopen", __LINE__, __FILE__);
				$endPages = TRUE;
			} else {
				// post the data to the server
				fputs($fp, $header.$xml);
				$i = 0;
				$xmlresponseobj = null;
				while (!feof($fp)) {
					$res = fgets($fp, 1024);
					$line[] = $res;
					if ($i >= 6)
						$xmlresponseobj .= $res;
					$i++;
				}
				fclose($fp);
				$obj1 = simplexml_load_string($xmlresponseobj); // Parse XML
				$array1 = json_decode(json_encode($obj1), TRUE); // Convert to array
				if (!isset($array1['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item']) || !is_array($array1['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item'])) {
					myadmin_log('domains', 'warning', __NAMESPACE__.'::'.__METHOD__.' returned '.json_encode($array1), __LINE__, __FILE__);
					$endPages = TRUE;
				} else {
					$domainArray = $array1['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item'];
					foreach ($array1['body']['data_block']['dt_assoc']['item'][4]['dt_assoc']['item'][0]['dt_array']['item'] as $idx => $domainData)
						$domains[$domainData['dt_assoc']['item'][1]] = $domainData['dt_assoc']['item'][2];
					if (sizeof($domainArray) < $limit)
						$endPages = TRUE;
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
	public static function redeem_domain($domain) {
		$username = OPENSRS_USERNAME;
		$privateKey = OPENSRS_KEY;
		$xml = '<?xml version=\'1.0\' encoding=\'UTF-8\' standalone=\'no\'?>
				<!DOCTYPE OPS_envelope SYSTEM \'ops.dtd\'>
				<OPS_envelope>
					<header>
						<version>0.9</version>
					</header>
					<body>
						<data_block>
							<dt_assoc>
								<item key="protocol">XCP</item>
								<item key="action">REDEEM</item>
								<item key="object">DOMAIN</item>
								<item key="attributes">
									<dt_assoc>
										<item key="domain">'.$domain.'</item>
									</dt_assoc>
								</item>
							</dt_assoc>
						</data_block>
					</body>
				</OPS_envelope>
			';
		$signature = md5(md5($xml.$privateKey).$privateKey);
		$prefix = 'ssl://';
		$host = 'rr-n1-tor.opensrs.net';
		$port = 55443;
		$url = '/';
		$header = '';
		$header .= "POST $url HTTP/1.0\r\n";
		$header .= "Content-Type: text/xml\r\n";
		$header .= 'X-Username: '.$username."\r\n";
		$header .= 'X-Signature: '.$signature."\r\n";
		$header .= 'Content-Length: '.mb_strlen($xml)."\r\n\r\n";
		// ssl:// requires OpenSSL to be installed
		$fp = fsockopen($prefix.$host, $port, $errno, $errstr, 30);
		if (!$fp) {
			$result2 = 'UnKnown Error';
			myadmin_log('domains', 'info', "OpenSRS::redeem_domain({$domain}) returned error {$errno} {$errstr} on fsockopen", __LINE__, __FILE__);
		} else {
			// post the data to the server
			fputs($fp, $header.$xml);
			$i = 0;
			$xmlresponseobj = null;
			while (!feof($fp)) {
				$res = fgets($fp);
				if ($i >= 6) {
					$xmlresponseobj .= $res;
				}
				$i++;
			}
			fclose($fp);
			$obj1 = simplexml_load_string($xmlresponseobj); // Parse XML
			$array1 = json_decode(json_encode($obj1), true); // Convert to array
			$resultArray = $array1['body']['data_block']['dt_assoc'];
			myadmin_log('domains', 'info', "OpenSRS::redeem_domain({$domain}) returned {$resultArray}", __LINE__, __FILE__);
			return $resultArray;
		}
	}
}
