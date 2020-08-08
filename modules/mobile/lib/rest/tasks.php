<?php
namespace Bitrix\Mobile\Rest;

use Bitrix\Tasks\Kanban\TimeLineTable;
use Bitrix\Tasks\Util\Type\DateTime;

/**
 * Class Tasks
 *
 * @package Bitrix\Mobile\Rest
 */
class Tasks extends \IRestService
{
	/**
	 * @return array|array[]
	 */
	public static function getMethods(): array
	{
		return [
			'mobile.tasks.deadlines.get' => [
				'callback' => [__CLASS__, 'getDeadlines'],
				'options' => ['private' => false],
			],
		];
	}

	/**
	 * @return array
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function getDeadlines(): array
	{
		$tomorrow = MakeTimeStamp(TimeLineTable::getDateClient().' 23:59:59') + 86400;
		$deadlines = ['tomorrow' => (new DateTime(TimeLineTable::getClosestWorkHour($tomorrow)))->getTimestamp()];
		$map = [
			'PERIOD2' => 'today',
			'PERIOD3' => 'thisWeek',
			'PERIOD4' => 'nextWeek',
			'PERIOD6' => 'moreThanTwoWeeks',
		];
		foreach (TimeLineTable::getStages() as $key => $val)
		{
			if (array_key_exists($key, $map))
			{
				$deadlines[$map[$key]] = (new DateTime($val['UPDATE']['DEADLINE']))->getTimestamp();
			}
		}

		return $deadlines;
	}
}