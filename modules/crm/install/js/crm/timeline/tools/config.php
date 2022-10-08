<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

return [
	'js' => 'dist/tools.bundle.js',
	'rel' => [
		'main.core',
		'main.date',
	],
	'skip_core' => false,
	'oninit' => static function()
	{
		$culture = \Bitrix\Main\Application::getInstance()->getContext()->getCulture();
		$time = new \DateTime();

		return [
			'lang_additional' => [
				'CRM_TIMELINE_TIME_FORMAT' => $culture->getShortTimeFormat(),
				'CRM_TIMELINE_SHORT_DATE_FORMAT' => $culture->getDayShortMonthFormat(),
				'CRM_TIMELINE_FULL_DATE_FORMAT' => $culture->getMediumDateFormat(),
				'CRM_TIMELINE_SERVER_TZ_OFFSET' => $time->getTimezone()->getOffset($time),
			]
		];
	}
];
