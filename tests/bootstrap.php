<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Define constants needed by the source code if not already defined
if (!defined('OPENSRS_USERNAME')) {
    define('OPENSRS_USERNAME', getenv('OPENSRS_USERNAME') ?: 'test_user');
}
if (!defined('OPENSRS_PASSWORD')) {
    define('OPENSRS_PASSWORD', getenv('OPENSRS_PASSWORD') ?: 'test_pass');
}
if (!defined('OPENSRS_KEY')) {
    define('OPENSRS_KEY', getenv('OPENSRS_KEY') ?: 'test_key');
}
if (!defined('OPENSRS_TEST_KEY')) {
    define('OPENSRS_TEST_KEY', getenv('OPENSRS_TEST_KEY') ?: 'test_key');
}
if (!defined('OPENSRS_PRIVACY_COST')) {
    define('OPENSRS_PRIVACY_COST', 5.00);
}
if (!defined('OUTOFSTOCK_OPENSRS_DOMAINS')) {
    define('OUTOFSTOCK_OPENSRS_DOMAINS', 0);
}
if (!defined('PRORATE_BILLING')) {
    define('PRORATE_BILLING', 1);
}
if (!defined('STATISTICS_SERVER')) {
    define('STATISTICS_SERVER', 'localhost');
}
if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.com');
}

// Stub global functions that the source code depends on but are not available in test context
if (!function_exists('myadmin_log')) {
    function myadmin_log($module, $level, $message, $line = 0, $file = '', $logModule = '', $id = 0)
    {
    }
}
if (!function_exists('get_module_settings')) {
    function get_module_settings($module)
    {
        return [
            'SERVICE_ID_OFFSET' => 10000,
            'USE_REPEAT_INVOICE' => true,
            'USE_PACKAGES' => true,
            'BILLING_DAYS_OFFSET' => 45,
            'IMGNAME' => 'domain.png',
            'REPEAT_BILLING_METHOD' => PRORATE_BILLING,
            'DELETE_PENDING_DAYS' => 45,
            'SUSPEND_DAYS' => 14,
            'SUSPEND_WARNING_DAYS' => 7,
            'TITLE' => 'Domain Registrations',
            'MENUNAME' => 'Domains',
            'EMAIL_FROM' => 'support@interserver.net',
            'TBLNAME' => 'Domains',
            'TABLE' => 'domains',
            'TITLE_FIELD' => 'domain_hostname',
            'PREFIX' => 'domain',
        ];
    }
}
if (!function_exists('get_service')) {
    function get_service($id, $module)
    {
        return false;
    }
}
if (!function_exists('run_event')) {
    function run_event($event, $data = false, $module = '')
    {
        return $data;
    }
}
if (!function_exists('get_service_addons')) {
    function get_service_addons($id, $module)
    {
        return [];
    }
}
if (!function_exists('request_log')) {
    function request_log($module, $custid, $func, $provider, $action, $request, $response, $id = 0)
    {
    }
}
if (!function_exists('add_output')) {
    function add_output($text)
    {
    }
}
if (!function_exists('function_requirements')) {
    function function_requirements($requirement)
    {
    }
}
if (!function_exists('get_service_define')) {
    function get_service_define($name)
    {
        return 0;
    }
}
if (!function_exists('page_title')) {
    function page_title($title)
    {
    }
}
if (!function_exists('get_module_db')) {
    function get_module_db($module)
    {
        return null;
    }
}
if (!function_exists('dialog')) {
    function dialog($title, $message, $extra = false, $options = '')
    {
    }
}
if (!function_exists('del_lock')) {
    function del_lock($name)
    {
    }
}
if (!function_exists('get_domain_tld')) {
    function get_domain_tld($domain)
    {
        $parts = explode('.', $domain, 2);
        return isset($parts[1]) ? '.' . $parts[1] : '';
    }
}
if (!function_exists('get_available_domain_tlds')) {
    function get_available_domain_tlds()
    {
        return ['.com', '.net', '.org'];
    }
}
if (!function_exists('get_available_domain_tlds_by_tld')) {
    function get_available_domain_tlds_by_tld()
    {
        return ['.com' => ['id' => 1, 'cost' => '10.00'], '.net' => ['id' => 2, 'cost' => '10.00']];
    }
}
if (!function_exists('get_service_tld_pricing')) {
    function get_service_tld_pricing()
    {
        return [];
    }
}
if (!function_exists('getDomainTermInfo')) {
    function getDomainTermInfo($tld)
    {
        return ['term' => 1];
    }
}
if (!function_exists('parse_domain_extra')) {
    function parse_domain_extra($extra)
    {
        return is_array($extra) ? $extra : [];
    }
}
if (!function_exists('get_orm_class_from_table')) {
    function get_orm_class_from_table($table)
    {
        return 'Product';
    }
}
if (!function_exists('domain_welcome_email')) {
    function domain_welcome_email($id, $renew)
    {
    }
}
if (!function_exists('myadmin_stringify')) {
    function myadmin_stringify($data)
    {
        return json_encode($data);
    }
}
if (!function_exists('obj2array')) {
    function obj2array($obj)
    {
        return (array) $obj;
    }
}
if (!function_exists('get_domain_error_text')) {
    function get_domain_error_text($handler)
    {
        return $handler->resultFullRaw['response_text'] ?? 'Unknown error';
    }
}
if (!function_exists('convert_country_iso2')) {
    function convert_country_iso2($country)
    {
        return $country;
    }
}
if (!function_exists('_randomstring')) {
    function _randomstring($length)
    {
        return str_repeat('a', $length);
    }
}

// Pre-load the StatisticClient from the parent vendor directory to avoid
// redeclaration conflicts when OpenSRS.php require_once's it.
// If not available (e.g., in CI), define a stub.
$statisticClientPath = __DIR__ . '/../../../workerman/statistics/Applications/Statistics/Clients/StatisticClient.php';
if (!class_exists('StatisticClient', false)) {
    if (file_exists($statisticClientPath)) {
        require_once $statisticClientPath;
    } else {
        class StatisticClient
        {
            public static function tick($module, $action)
            {
            }

            public static function report($module, $action, $success, $code = 0, $message = '', $server = '')
            {
            }
        }
    }
}
