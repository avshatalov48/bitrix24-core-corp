<?php

/**
 * @access private
 */

namespace Bitrix\Tasks\Integration\SocialNetwork;

use Bitrix\Main\Application;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Socialnetwork\LogRightTable;
use Bitrix\SocialNetwork\LogTable;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Internals\UserOption;

class Log extends SocialNetwork
{
	const EVENT_ID_TASK = 'tasks';

	/**
	 * Returns set EVENT_ID processed by handler to generate content for full index.
	 *
	 * @param void
	 * @return array
	 */
	public static function getEventIdList(): array
	{
		return [
			self::EVENT_ID_TASK,
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
			'tasks'
		);

		$eventId = $event->getParameter('eventId');
		$sourceId = $event->getParameter('sourceId');

		if (!in_array($eventId, self::getEventIdList()))
		{
			return $result;
		}

		$content = '';
		$task = false;

		if (intval($sourceId) > 0)
		{
			$task = new \Bitrix\Tasks\Item\Task($sourceId);
		}

		if ($task)
		{
			$controllerDefault = $task->getAccessController();
			$controller = $controllerDefault->spawn();
			$controller->disable();
			$task->setAccessController($controller);

			$taskFields = $task->getData('#', array('bSkipExtraData' => false));
			if (is_array($taskFields))
			{
				$content = \Bitrix\Tasks\Manager\Task::prepareSearchIndex($taskFields);
			}

			$controller->enable();
		}

		return new EventResult(
			EventResult::SUCCESS,
			array(
				'content' => $content,
			),
			'tasks'
		);
	}

	/**
	 * @param $logId
	 * @param $type
	 * @param $userId
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function onAfterLogFollowSet($logId, $type, $userId)
	{
		if (!static::includeModule())
		{
			return;
		}

		$taskId = 0;
		$userId = (int)$userId;

		$res = LogTable::getList([
			'select' => ['SOURCE_ID'],
			'filter' => [
				'=ID' => $logId,
				'=EVENT_ID' => 'tasks',
			],
		]);
		if ($data = $res->fetch())
		{
			$taskId = (int)$data['SOURCE_ID'];
		}

		if ($type === 'Y')
		{
			UserOption::delete($taskId, $userId, UserOption\Option::MUTED);
		}
		else
		{
			UserOption::add($taskId, $userId, UserOption\Option::MUTED);
		}
	}

	public static function onAfterSocNetLogCommentAdd($id, $arFields)
	{
		if (
			!is_array($arFields)
			|| !isset($arFields['SOURCE_ID'])
			|| !isset($arFields['LOG_ID'])
			|| !isset($arFields['EVENT_ID'])
		)
		{
			return;
		}

		if (
			$arFields['EVENT_ID'] !== 'tasks_comment'
		)
		{
			return;
		}

		$shareDest = isset($arFields['SHARE_DEST']) ? unserialize($arFields['SHARE_DEST'], ['allowed_classes' => false]) : null;

		$isNew = false;
		if (
			isset($_POST['ACTION'][0]['OPERATION'])
			&& $_POST['ACTION'][0]['OPERATION'] === 'task.add'
		)
		{
			$isNew = true;
		}
		elseif (
			is_array($shareDest)
			&& isset($shareDest[0][0][0])
			&& strpos($shareDest[0][0][0], 'COMMENT_POSTER_COMMENT_TASK_ADD') === 0
		)
		{
			$isNew = true;
		}

		$userId = (int) $arFields['USER_ID'];
		$logId = (int) $arFields['LOG_ID'];
		self::updateLogRights($logId, $userId, $isNew);
	}

	public static function updateLogRights(int $logId, int $userId, bool $isNew = false)
	{
		if (!static::includeModule())
		{
			return;
		}

		$log = LogTable::getById($logId)->fetch();
		if (!$log)
		{
			return;
		}

		$taskId = (int) $log['SOURCE_ID'];

		$task = self::getTask($taskId);
		if (!$task)
		{
			return;
		}

		$rights = [];

		if ($userId && !$isNew)
		{
			$rights['U'.$userId] = 'U'.$userId;
		}

		$members = self::getTaskMembers($taskId);
		foreach ($members as $member)
		{
			if (
				$isNew
				&& $member['TYPE'] == MemberTable::MEMBER_TYPE_ORIGINATOR
				&& $userId == $member['USER_ID']
			)
			{
				continue;
			}

			$code = "U{$member['USER_ID']}";
			if (!array_key_exists($code, $rights))
			{
				$rights[$code] = $code;
			}
		}

		if ($task['GROUP_ID'])
		{
			$rights['SG'.$task['GROUP_ID']] = 'SG'.$task['GROUP_ID'];
		}

		// drop all rights
		\CSocNetLogRights::DeleteByLogID($log['ID']);

		$connection = Application::getConnection();

		foreach ($rights as $row)
		{
			$logId = (int) $log['ID'];
			$logUpdate = $connection->getSqlHelper()->getCurrentDateTimeFunction();

			$sql = $connection->getSqlHelper()->getInsertIgnore(
				LogRightTable::getTableName(),
				' (LOG_ID, GROUP_CODE, LOG_UPDATE)',
				" VALUES ({$logId}, '{$row}', $logUpdate)"
			);

			$connection->query($sql);
		}

	}

	public static function hideLogByTaskId($taskId)
	{
		if (!static::includeModule())
		{
			return;
		}

		$log = self::getLogByTaskId((int) $taskId);
		if (!$log)
		{
			return;
		}
		if ($log['INACTIVE'] !== 'Y')
		{
			LogTable::update($log['ID'], ['INACTIVE' => 'Y']);
		}
	}

	public static function showLogByTaskId($taskId)
	{
		if (!static::includeModule())
		{
			return;
		}

		$log = self::getLogByTaskId((int) $taskId);
		if (!$log)
		{
			return;
		}
		if ($log['INACTIVE'] === 'Y')
		{
			LogTable::update($log['ID'], ['INACTIVE' => 'N']);
		}
	}

	public static function deleteLogByTaskId($taskId)
	{
		if (!static::includeModule())
		{
			return;
		}

		$log = self::getLogByTaskId((int) $taskId);
		if (!$log)
		{
			return;
		}
		LogTable::delete($log['ID']);
	}

	private static function getTask(int $taskId)
	{
		return TaskTable::query()
			->addSelect('ID')
			->addSelect('GROUP_ID')
			->where('ID', $taskId)
			->exec()
			->fetch();
	}

	private static function getTaskMembers(int $taskId): array
	{
		$members = [];

		$res = MemberTable::query()
			->addSelect('USER_ID')
			->addSelect('TYPE')
			->where('TASK_ID', $taskId)
			->exec()
			->fetchAll();

		foreach ($res as $member)
		{
			$members[] = $member;
		}

		return $members;
	}

	private static function getLogByTaskId(int $taskId)
	{
		$dbRes = LogTable::getList([
			'select' => ['ID', 'INACTIVE'],
			'filter' => [
				'=EVENT_ID' => 'tasks',
				'=SOURCE_ID' => $taskId
			],
			'limit' => 1
		]);

		return $dbRes->fetch();
	}
}
