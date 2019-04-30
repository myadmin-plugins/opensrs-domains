#!/usr/bin/env php
<?php

//require_once __DIR__ . "/../../../../include/functions.inc.php";
require_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../detain/myadmin-opensrs-domains/src/openSRS_loader.php';
use opensrs\Request;

$callstring = json_encode(
	[
		'func' => 'lookupDomain', 'attributes' => [
					'domain' => $_SERVER['argv'][1],
					//'selected' => implode(';', get_available_domain_tlds())
					//'selected' => $_SERVER['argv'][2],
	]
	]
);
try {
	$request = new Request();
	$osrsHandler = $request->process('json', $callstring);

	print('In: ' . json_encode(json_decode($callstring), JSON_PRETTY_PRINT) . "\n");
	print('Out: ' .print_r($osrsHandler->resultFullRaw, true) . "\n");
} catch (\opensrs\Exception $e) {
	var_dump($e->getMessage());
}
