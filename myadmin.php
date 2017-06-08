<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_opensrs define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Opensrs Domains',
	'description' => 'Allows selling of Opensrs Server and VPS License Types.  More info at https://www.netenberg.com/opensrs.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a opensrs license. Allow 10 minutes for activation.',
	'module' => 'domains',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-opensrs-domains',
	'repo' => 'https://github.com/detain/myadmin-opensrs-domains',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminOpensrs\Plugin', 'Requirements'],
		'domains.settings' => ['Detain\MyAdminOpensrs\Plugin', 'Settings'],
		'domains.activate' => ['Detain\MyAdminOpensrs\Plugin', 'Activate'],
		'domains.change_ip' => ['Detain\MyAdminOpensrs\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminOpensrs\Plugin', 'Menu'] */
	],
];
