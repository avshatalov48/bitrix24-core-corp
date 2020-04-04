<?php
namespace Bitrix\Tasks\Integration\Pull;

use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;

class Counter
{
	const TYPE_TASK = 'total';
	const MODULE_ID = 'tasks';

	public static function onGetMobileCounterTypes(\Bitrix\Main\Event $event)
	{
		return new EventResult(EventResult::SUCCESS, Array(
			self::TYPE_TASK => Array(
				'NAME' => Loc::getMessage('TASKS_COUNTER_TYPE_TASKS'),
				'DEFAULT' => false
			)
		), self::MODULE_ID);
	}

	public static function onGetMobileCounter(\Bitrix\Main\Event $event)
	{
		$params = $event->getParameters();

		$counters = \CUserCounter::getGroupedCounters(
			\CUserCounter::GetAllValues($params['USER_ID'])
		);
		$counterType = "tasks_total";
		$counter = isset($counters[$params['SITE_ID']][$counterType])? $counters[$params['SITE_ID']][$counterType]: 0;
		$counter = $counter > 0? $counter: 0;

		return new EventResult(EventResult::SUCCESS, [
			'TYPE' => self::TYPE_TASK,
			'COUNTER' => $counter
		], self::MODULE_ID);
	}
}