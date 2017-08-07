<?php

defined('OPENSRSURI') or require_once __DIR__.'/openSRS_config.php';

use opensrs\Request;

/**
 * Method to convert Array -> Object -> Array.
 *
 * @param hash|array $data Containing array object
 * @return \hash|\stdClass
 * @since    3.4
 */
function array2object($data) {
	if (!is_array($data))
		return $data;
	$object = new stdClass();
	foreach ($data as $name => $value)
		if (isset($name)) {
			$name = strtolower(trim($name));
			$object->$name = array2object($value);
		}
	return $object;
}

	/**
	 * @param $data
	 * @return array
	 */
	function object2array($data) {
	if (!is_object($data) && !is_array($data)) {
		return $data;
	}
	if (is_object($data)) {
		$data = get_object_vars($data);
	}

	return array_map('object2array', $data);
}

// Call parsers and functions of openSRS
/**
 * @param string $type
 * @param string $data
 * @return mixed
 */
function processOpenSRS($type = '', $data = '') {
	try {
		$request = new Request();

		return $request->process($type, $data);
	} catch (Exception $e) {
		trigger_error($e->getMessage(), E_USER_WARNING);
	}
}

/**
 * @param string $type
 * @param string $data
 * @return string
 */
function convertArray2Formatted($type = '', $data = '') {
	$resultString = '';
	if ($type == 'json') {
		$resultString = json_encode($data);
	}
	if ($type == 'yaml') {
		$resultString = Spyc::YAMLDump($data);
	}

	return $resultString;
}

/**
 * @param string $type
 * @param string $data
 * @return mixed|string
 */
function convertFormatted2array($type = '', $data = '') {
	$resultArray = '';
	if ($type == 'json') {
		$resultArray = json_decode($data, TRUE);
	}
	if ($type == 'yaml') {
		$resultArray = Spyc::YAMLLoad($data);
	}

	return $resultArray;
}

/**
 * @param $input
 * @return array
 */
function array_filter_recursive($input) {
	foreach ($input as &$value) {
		if (is_array($value)) {
			$value = array_filter_recursive($value);
		}
	}

	return array_filter($input);
}
