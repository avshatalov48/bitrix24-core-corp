<?php
use Bitrix\Main\Context;

$culture = Context::getCurrent()->getCulture();
return [
	'locale'=>$culture->getName(),
	'formats' => [
		'shortTime' => $culture->getShortTimeFormat(),
		'dayMonth' => $culture->getDayMonthFormat(),
		'date' => $culture->getFormatDate(),
		'datetime' => $culture->getFormatDatetime(),
		'dayOfWeekMonth' => $culture->getDayOfWeekMonthFormat(),
		'dayShortMonth' => $culture->getDayShortMonthFormat(),
		'fullDate' => $culture->getFullDateFormat(),
		'longTime' => $culture->getLongTimeFormat(),
		'longDate' => $culture->getLongDateFormat(),
		'mediumDate' => $culture->getMediumDateFormat(),
	]
];