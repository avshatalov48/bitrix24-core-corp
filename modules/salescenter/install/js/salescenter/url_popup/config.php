<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'css' => '/bitrix/js/salescenter/url_popup/dist/url_popup.bundle.css',
	'js' => '/bitrix/js/salescenter/url_popup/dist/url_popup.bundle.js',
	'rel' => [
		'main.core',
		'main.popup',
		'ui.vue',
		'salescenter.manager',
		'ui.forms',
	],
	'skip_core' => false,
];