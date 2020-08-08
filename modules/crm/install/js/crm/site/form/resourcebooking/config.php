<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;

return [
	'js' => [
		'compatibility.js',
	],
	"rel" => [
		'ui.webpacker', 'ui.vue.components.datepick', 'calendar.resourcebooking',
	],
	"skip_core" => true,
	"options" => [
		"webpacker" => [
			"useAllLangs" => false,
			"useLangCamelCase" => false,
			"properties" => [
				"AM_PM_NONE" => AM_PM_NONE,
				"AM_PM_UPPER" => AM_PM_UPPER,
				"AM_PM_LOWER" => AM_PM_LOWER,
				"AMPM_MODE" => IsAmPmMode(true),
				"FORMAT_DATE" => Context::getCurrent()->getCulture()->getDateFormat(),
				"FORMAT_DATETIME" => Context::getCurrent()->getCulture()->getDateTimeFormat(),
			],
		]
	]
];