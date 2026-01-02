<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Type\Date;

$culture = Context::getCurrent()->getCulture();

return [
	'locale' => $culture->getName(),
	'markers' => [
		'am' => $culture->getAmValue(),
		'pm' => $culture->getPmValue(),
	],
	'formats' => [
		'SHORT_TIME_FORMAT' => $culture->getShortTimeFormat(),
		'DAY_MONTH_FORMAT' => $culture->getDayMonthFormat(),
		'FORMAT_DATE' => Date::convertFormatToPhp($culture->getFormatDate()),
		'SHORT_DATE_FORMAT' => Date::convertFormatToPhp($culture->getFormatDate()),
		'FORMAT_DATETIME' => Date::convertFormatToPhp($culture->getFormatDatetime()),
		'DAY_OF_WEEK_MONTH_FORMAT' => $culture->getDayOfWeekMonthFormat(),
		'SHORT_DAY_OF_WEEK_MONTH_FORMAT' => $culture->getShortDayOfWeekMonthFormat(),
		'SHORT_DAY_OF_WEEK_SHORT_MONTH_FORMAT' => $culture->getShortDayOfWeekShortMonthFormat(),
		'DAY_SHORT_MONTH_FORMAT' => $culture->getDayShortMonthFormat(),
		'FULL_DATE_FORMAT' => $culture->getFullDateFormat(),
		'LONG_TIME_FORMAT' => $culture->getLongTimeFormat(),
		'LONG_DATE_FORMAT' => $culture->getLongDateFormat(),
		'MEDIUM_DATE_FORMAT' => $culture->getMediumDateFormat(),
	],
];
