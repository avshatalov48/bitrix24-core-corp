<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => '/bitrix/js/crm/router/dist/router.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'sidepanel',
	],
	'skip_core' => false,
];