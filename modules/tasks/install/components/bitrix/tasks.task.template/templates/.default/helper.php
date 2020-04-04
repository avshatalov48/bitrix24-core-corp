<?
use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Calendar;
use Bitrix\Tasks\Util\Type\DateTime;
use Bitrix\Tasks\Util\Type\ArrayOption;
use Bitrix\Tasks\Util\Type\StructureChecker;
use Bitrix\Tasks\Util\Collection;
use Bitrix\Tasks\Integration\CRM;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

return new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array(
		'tasks_util_datepicker',
		'popup',
		'fx',
		'tasks_util_widget',
		'tasks_util_itemset',
		'tasks_util',
		'tasks_itemsetpicker',
		'tasks_util_query',
		'tasks_shared_form_projectplan',
		'task_calendar',
		'tasks'
	),
	'METHODS' => array(
		'detectUnitType' => function($matchWorkTime, $value)
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
			$realValue = $value;

			if(!($value % $dayDuration))
			{
				$unit = 'days';
				$value = floor($value / $dayDuration);
			}
			elseif(!($value % 3600))
			{
				$unit = 'hours';
				$value = floor($value / 3600);
			}
			else
			{
				$unit = 'mins';
				$value = floor($value / 60);
			}

			if(!$value)
			{
				$value = '';
			}

			return array('UNIT' => $unit, 'VALUE' => $value, 'REAL_VALUE' => $realValue);
		}
	),
));