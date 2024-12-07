<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arComponentParameters = [
	'PARAMETERS' => [

		'CONTROLLER_URL' => [
			'NAME' => GetMessage('CSA_PARAM_CONTROLLER_URL'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => 'http://controller.mysite.com/',
		],

		'URL_SUBDOMAIN' => [
			'NAME' => GetMessage('CSA_PARAM_URL_SUBDOMAIN'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => 'mysite.com',
		],

		'PATH_VHOST' => [
			'NAME' => GetMessage('CSA_PARAM_PATH_VHOST'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '../sites',
		],

		'MYSQL_PATH' => [
			'NAME' => GetMessage('CSA_PARAM_MYSQL_PATH'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '/usr/bin/mysql',
		],

		'MYSQL_USER' => [
			'NAME' => GetMessage('CSA_PARAM_MYSQL_USER'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => 'root',
		],

		'MYSQL_PASSWORD' => [
			'NAME' => GetMessage('CSA_PARAM_MYSQL_PASSWORD'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '',
		],

		'MYSQL_DB_PATH' => [
			'NAME' => GetMessage('CSA_PARAM_MYSQL_DB_PATH'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '',
		],

		'DIR_PERMISSIONS' => [
			'NAME' => GetMessage('CSA_PARAM_DIR_PERMISSIONS'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '0775',
		],

		'FILE_PERMISSIONS' => [
			'NAME' => GetMessage('CSA_PARAM_FILE_PERMISSIONS'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '0664',
		],

		'PATH_PUBLIC' => [
			'NAME' => GetMessage('CSA_PARAM_PATH_PUBLIC'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => 'client/public',
		],

		'MEMORY_LIMIT' => [
			'NAME' => GetMessage('CSA_PARAM_MEMORY_LIMIT'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '8M',
		],

		'APACHE_ROOT' => [
			'NAME' => GetMessage('CSA_PARAM_APACHE_ROOT'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '/etc/apache/vhosts',
		],

		'NGINX_ROOT' => [
			'NAME' => GetMessage('CSA_PARAM_NGINX_ROOT'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '/etc/nginx/vhosts',
		],

		'RELOAD_FILE' => [
			'NAME' => GetMessage('CSA_PARAM_RELOAD_FILE'),
			'TYPE' => 'STRING',
			'COLS' => 45,
			'DEFAULT' => '/tmp/apache_reload',
		],

		'REGISTER_IMMEDIATE' => [
			'NAME' => GetMessage('CSA_PARAM_REGISTER_IMMEDIATE'),
			'TYPE' => 'CHECKBOX',
			'DEFAULT' => 'Y',
		],

		'SET_TITLE' => [],
	]
];
