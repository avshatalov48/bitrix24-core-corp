<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();

return [
	'js' => [
		'compatibility.js',
	],
	'rel' => [
		'ui.webpacker',
		'booking.crm-forms.field',
	],
	'skip_core' => true,
	'options' => [
		'webpacker' => [
			'useAllLangs' => false,
			'useLangCamelCase' => false,
			'properties' => [
				'DAY_MONTH_FORMAT' => $culture->get('DAY_MONTH_FORMAT'),
				'SHORT_TIME_FORMAT' => $culture->get('SHORT_TIME_FORMAT'),
			],
		],
	],
];
