<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js'  => [
		'/bitrix/js/crm/report/salescompareperiods/view.js',
	],
	'css'  => [
		'/bitrix/js/crm/report/salescompareperiods/view.css',
	],
	'rel' => [
		'ui.design-tokens',
		'ui.fonts.opensans',
		'date',
	]
];
