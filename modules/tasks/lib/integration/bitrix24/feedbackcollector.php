<?php

namespace Bitrix\Tasks\Integration\Bitrix24;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Internals\Task\LogTable;

class FeedbackCollector extends Bitrix24
{
	public const TASKS_COUNT_ON_SLIDER_CLOSE = 3;

	/**
	 * @param Event $event
	 * @return EventResult
	 */
	public static function onFeedbackCollectorCheckCanRun(Event $event): EventResult
	{
		$canRun = false;

		$feedbackId = $event->getParameter('feedbackId');
		$userId = $event->getParameter('userId');

		if ($feedbackId === 'tasksFeedbackSliderClose')
		{
			$canRun = static::checkCanRunFeedbackOnSliderClose($userId);
		}

		return new EventResult(EventResult::SUCCESS, ['canRun' => $canRun], 'tasks');
	}

	private static function checkCanRunFeedbackOnSliderClose(int $userId): bool
	{
		$result = LogTable::getList([
			'select' => ['ID'],
			'filter' => [
				'USER_ID' => $userId,
				'FIELD' => 'NEW',
			],
			'limit' => static::TASKS_COUNT_ON_SLIDER_CLOSE,
		])->fetchAll();

		return (count($result) >= static::TASKS_COUNT_ON_SLIDER_CLOSE);
	}
}