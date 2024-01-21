<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => 'dist/apache-superset-feedback-form.bundle.css',
	'js' => 'dist/apache-superset-feedback-form.bundle.js',
	'rel' => [
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'fromDomain' =>
			defined('BX24_HOST_NAME')
			? BX24_HOST_NAME
			: Bitrix\Main\Config\Option::get('main', 'server_name', '')
		,
	],
];