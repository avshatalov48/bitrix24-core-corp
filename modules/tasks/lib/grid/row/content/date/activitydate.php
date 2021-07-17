<?php
namespace Bitrix\Tasks\Grid\Row\Content\Date;

use Bitrix\Tasks\Grid\Row\Content\Date;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\Template\ComponentAssistant;
use CTasks;

/**
 * Class ActivityDate
 *
 * @package Bitrix\Tasks\Grid\Row\Content\Date
 */
class ActivityDate extends Date
{
	/**
	 * @param array $row
	 * @param array $parameters
	 * @return string
	 */
	public static function prepare(array $row, array $parameters): string
	{
		$counter = '';

		if (
			array_key_exists('COUNTERS_LIST', $row)
			&& is_array($row['COUNTERS_LIST'])
		)
		{
			$rowCounter = ComponentAssistant::getRowCounter($row['COUNTERS_LIST']);
			if ($rowCounter['VALUE'])
			{
				$counter = "<div class='ui-counter ui-counter-{$rowCounter['COLOR']}'><div class='ui-counter-inner'>{$rowCounter['VALUE']}</div></div>";
			}
		}


		$activityDate = static::formatDate($row['ACTIVITY_DATE']);
		$counterContainer = "<span class='task-counter-container'>{$counter}</span>";

		return $counterContainer."<span id='changedDate' style='margin-left: 3px'>{$activityDate}</span>";
	}
}