<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 */

namespace Bitrix\Tasks\Util\Notification;

use Bitrix\Im\User;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Integration\IM;
use Bitrix\Tasks\Internals\Runtime;
use Bitrix\Tasks\Internals\Task\MemberTable;
use Bitrix\Tasks\Internals\TaskTable;
use Bitrix\Tasks\Util\Type\DateTime;

Loc::loadMessages(__FILE__);

final class Task
{
	public static function createOverdueChats()
	{
		if (
			$GLOBALS['__TASKS_DEVEL_ENV__']
			|| Option::get('tasks', 'create_overdue_chats', 'N') === 'N'
		)
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

	private static function getOverdueTasks()
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
			'=STATUS' => [\CTasks::STATE_PENDING, \CTasks::STATE_IN_PROGRESS],
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

		return array_filter($tasks, function($item) use($uniqueMembers) {
			return count($uniqueMembers[$item['ID']]) > 1;
		});
	}

	private static function getDayStartDateTime()
	{
		$now = new DateTime();
		$structure = $now->getTimeStruct();
		$now->add('-T'.($structure['SECOND'] + 60*$structure['MINUTE'] + 3600*$structure['HOUR']).'S');

		return $now;
	}
}