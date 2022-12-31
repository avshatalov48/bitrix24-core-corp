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
		'shortTime' => $culture->getShortTimeFormat(),
		'dayMonth' => $culture->getDayMonthFormat(),
		'date' => Date::convertFormatToPhp($culture->getFormatDate()),
		'datetime' => Date::convertFormatToPhp($culture->getFormatDatetime()),
		'dayOfWeekMonth' => $culture->getDayOfWeekMonthFormat(),
		'dayShortMonth' => $culture->getDayShortMonthFormat(),
		'fullDate' => $culture->getFullDateFormat(),
		'longTime' => $culture->getLongTimeFormat(),
		'longDate' => $culture->getLongDateFormat(),
		'mediumDate' => $culture->getMediumDateFormat(),
	],
];
