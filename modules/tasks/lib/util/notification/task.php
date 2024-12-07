<?php
namespace Bitrix\Tasks\Util\Notification;

use Bitrix\Im\User;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\IM;
use Bitrix\Tasks\Internals\Runtime;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util\Type\DateTime;

Loc::loadMessages(__FILE__);

/**
 * Class Task
 *
 * @package Bitrix\Tasks\Util\Notification
 */
final class Task
{
	/**
	 * If there is no chat yet, creates it and posts message about task's expiration.
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function createOverdueChats(): void
	{
		if (Option::get('tasks', 'create_overdue_chats', 'N') === 'N')
		{
			return;
		}

		if (IM\Task::includeModule())
		{
			$overdueTasks = static::getOverdueTasks();
			foreach ($overdueTasks as $task)
			{
				if ($chatId = IM\Task::openChat($task))
				{
					$gender = (User::getInstance($task['RESPONSIBLE_ID'])->getGender() ?: 'N');
					$message = Loc::getMessage('TASKS_IM_MESSAGE_OVERDUE_'.$gender).' :cry:';

					IM\Task::postMessage($chatId, $message, $task);
				}
			}
		}
	}

	/**
	 * Returns overdue tasks for today with more than 1 unique member
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getOverdueTasks(): array
	{
		$query = new Query(TaskTable::getEntity());
		$query->setSelect([
			'ID',
			'TITLE',
			'DESCRIPTION',
			'DEADLINE',
			'STATUS',
			'TM_USER_ID' => 'TM.USER_ID',
			'TM_TYPE' => 'TM.TYPE',
		]);
		$query->setFilter([
			'!DEADLINE' => null,
			'>=DEADLINE' => static::getDayStartDateTime(), // day start
			'<=DEADLINE' => new DateTime(), // current time
			'=STATUS' => [Status::PENDING, Status::IN_PROGRESS],
		]);
		$query->registerRuntimeField('', new ReferenceField(
			'TM',
			MemberTable::getEntity(),
			['=ref.TASK_ID' => 'this.ID']
		));
		Runtime::apply($query, [IM\Internals\RunTime::applyChatNotExist()]);
		$res = $query->exec();

		$tasks = [];
		$uniqueMembers = [];

		while ($item = $res->fetch())
		{
			$taskId = $item['ID'];
			$userId = $item['TM_USER_ID'];
			$userType = $item['TM_TYPE'];

			unset($item['TM_USER_ID'], $item['TM_TYPE']);

			if (!array_key_exists($taskId, $tasks))
			{
				$item['SE_MEMBER'][$userId] = ['USER_ID' => $userId, 'TYPE' => $userType];
				$tasks[$taskId] = $item;
			}
			else
			{
				$tasks[$taskId]['SE_MEMBER'][$userId] = ['USER_ID' => $userId, 'TYPE' => $userType];
			}

			if (!array_key_exists($taskId, $uniqueMembers))
			{
				$uniqueMembers[$taskId] = [];
			}
			if (!array_key_exists($userId, $uniqueMembers[$taskId]))
			{
				$uniqueMembers[$taskId][$userId] = 1;
			}

			$roleMap = ['O' => 'CREATED_BY', 'R' => 'RESPONSIBLE_ID'];
			if (array_key_exists($userType, $roleMap))
			{
				$tasks[$taskId][$roleMap[$userType]] = $userId;
			}
		}

		return array_filter(
			$tasks,
			static function($item) use($uniqueMembers)
			{
				return count($uniqueMembers[$item['ID']]) > 1;
			}
		);
	}

	/**
	 * Returns start time of current day (00:00:00)
	 *
	 * @return DateTime
	 * @throws \Bitrix\Main\ObjectException
	 */
	private static function getDayStartDateTime(): DateTime
	{
		$now = new DateTime();
		$structure = $now->getTimeStruct();
		$now->add('-T'.($structure['SECOND'] + 60 * $structure['MINUTE'] + 3600 * $structure['HOUR']).'S');

		return $now;
	}
}