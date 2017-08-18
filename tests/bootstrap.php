<?php
require_once __DIR__.'/../vendor/autoload.php';
$GLOBALS['modules'] = ['domains' => [
        'SERVICE_ID_OFFSET' => 10000,
        'USE_REPEAT_INVOICE' => true,
        'USE_PACKAGES' => true,
        'BILLING_DAYS_OFFSET' => 45,
        'IMGNAME' => 'server_add_48.png',
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
        'PREFIX' => 'domain'
]];
define('OPENSRS_USERNAME', getenv('OPENSRS_USERNAME'));
define('OPENSRS_PASSWORD', getenv('OPENSRS_PASSWORD'));
define('OPENSRS_KEY', getenv('OPENSRS_KEY'));
define('OPENSRS_TEST_KEY', getenv('OPENSRS_TEST_KEY'));
