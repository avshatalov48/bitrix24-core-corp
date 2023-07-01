<?php

/**
 * @access private
 */

namespace Bitrix\Crm\Integration\SocialNetwork;

use Bitrix\Crm\Activity\Provider\Tasks\Task;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\SocialNetwork\LogTable;

class Log
{
	const EVENT_ID_CRM_ACTIVITY_ADD = 'crm_activity_add';

	/**
	 * Returns set EVENT_ID processed by handler to generate content for full index.
	 *
	 * @param void
	 * @return array
	 */
	public static function getEventIdList(): array
	{
		return [
			self::EVENT_ID_CRM_ACTIVITY_ADD,
		];
	}

	/**
	 * Returns content for LogIndex.
	 *
	 * @param Event $event Event from LogIndex::setIndex().
	 * @return EventResult
	 */
	public static function onIndexGetContent(Event $event): EventResult
	{
		$result = new EventResult(
			EventResult::UNDEFINED,
			[],
			'crm'
		);

		$itemId = (int)$event->getParameter('itemId');
		$eventId = $event->getParameter('eventId');

		if (
			$itemId <= 0
			|| !in_array($eventId, self::getEventIdList())
			|| !Loader::includeModule('socialnetwork')
		)
		{
			return $result;
		}

		$activityId = 0;

		$res = LogTable::getList([
			'filter' => [
				'=ID' => $itemId,
				'=EVENT_ID' => $eventId,
			],
			'select' => [ 'ENTITY_ID' ],
		]);
		if ($logFields = $res->fetch())
		{
			$activityId = (int)$logFields['ENTITY_ID'];
		}

		if ($activityId <= 0)
		{
			return $result;
		}

		$taskId = 0;

		$res = \CCrmActivity::getList(
			[],
			[
				'ID' => $activityId,
				'CHECK_PERMISSIONS' => 'N',
			],
			false,
			false,
			['ASSOCIATED_ENTITY_ID', 'TYPE_ID', 'PROVIDER_ID']
		);
		if (
			($activityFields = $res->fetch())
			&& ((int)$activityFields['ASSOCIATED_ENTITY_ID'] > 0)
		)
		{
			if (
				(int)$activityFields['TYPE_ID'] === \CCrmActivityType::Task
				|| (
					(int)$activityFields['TYPE_ID'] === \CCrmActivityType::Provider
					&& $activityFields['PROVIDER_ID'] === Task::getId()
				)
			)
			{
				$taskId = (int)$activityFields['ASSOCIATED_ENTITY_ID'];
			}
		}

		if (
			$taskId <= 0
			|| !Loader::includeModule('tasks')
		)
		{
			return $result;
		}

		$content = '';
		$task = new \Bitrix\Tasks\Item\Task($taskId);
		$controllerDefault = $task->getAccessController();
		$controller = $controllerDefault->spawn();
		$controller->disable();
		$task->setAccessController($controller);

		$taskFields = $task->getData('#', [ 'bSkipExtraData' => false ]);
		if (is_array($taskFields))
		{
			$content = \Bitrix\Tasks\Manager\Task::prepareSearchIndex($taskFields);
		}

		$controller->enable();

		return new EventResult(
			EventResult::SUCCESS,
			[
				'content' => $content,
			],
			'crm'
		);
	}
}