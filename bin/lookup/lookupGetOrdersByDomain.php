#!/usr/bin/env php
<?php

require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php';
use opensrs\Request;

$callstring = json_encode(
	[
		'func' => 'lookupGetOrdersByDomain', 'attributes' => [
		'domain'        => 'hussfamily.com',
		'bypass'        => '',
		'registrant_ip' => '',
		'limit'         => '10',
		'page'          => '',
		'max_to_expiry' => '',
		'min_to_expiry' => ''
	]
	]
);
try {
	$request = new Request();
	$osrsHandler = $request->process('json', $callstring);

	print('In: ' . $callstring . "\n");
	print('Out: ' . json_encode(json_decode($osrsHandler->resultFormatted), JSON_PRETTY_PRINT) . "\n");
} catch (\opensrs\Exception $e) {
	var_dump($e->getMessage());
}
