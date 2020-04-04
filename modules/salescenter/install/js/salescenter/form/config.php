<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => '/bitrix/js/salescenter/form/dist/form.bundle.css',
	'js' => '/bitrix/js/salescenter/form/dist/form.bundle.js',
	'rel' => [
		'main.core',
		'ui.forms',
	],
	'skip_core' => false,
];