<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\Calendar;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
return new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array('tasks_util', /*etc*/),
	'METHODS' => array(
		'formatDateAfter' => function($matchWorkTime, $value)
		{
			$dayDuration = 86400;

			if ($matchWorkTime)
			{
				$calendarSettings = Calendar::getSettings();

				$start = $calendarSettings['HOURS']['START'];
				$end = $calendarSettings['HOURS']['END'];

				$dayDuration = ($end['H'] - $start['H']) * 3600 + ($end['M'] - $start['M']) * 60 + ($end['S'] - $start['S']);
				$dayDuration = ($dayDuration > 0? $dayDuration : 86400);
			}

			$value = intval($value); // in seconds

			if(!($value % $dayDuration))
			{
				$unit = 'DAY';
				$value = floor($value / $dayDuration);
			}
			elseif(!($value % 3600))
			{
				$unit = 'HOUR';
				$value = floor($value / 3600);
			}
			else
			{
				$unit = 'MINUTE';
				$value = floor($value / 60);
			}

			return $value.' '.Loc::getMessagePlural('TASKS_COMMON_' . $unit, $value);
		}
	),
));