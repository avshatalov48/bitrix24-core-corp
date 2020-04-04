<?php
namespace Bitrix\Tasks\Rest\Controllers\Task;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\AutoWire\Parameter;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\Internals\Task\ViewedTable;
use Bitrix\Tasks\Rest\Controllers\Base;

use CTaskItem;
use Exception;

Loc::loadMessages(__FILE__);

/**
 * Class View
 *
 * @package Bitrix\Tasks\Rest\Controllers\Task
 */
class View extends Base
{
	/**
	 * @return ExactParameter|Parameter|null
	 */
	public function getPrimaryAutoWiredParameter()
	{
		return new ExactParameter(
			CTaskItem::class, 'task', function ($className, $id) {
			$userId = CurrentUser::get()->getId();

			return new $className($id, $userId);
		}
		);
	}

	/**
	 * @param CTaskItem $task
	 * @throws ArgumentException
	 * @throws ObjectException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 * @throws Exception
	 */
	public function updateAction(CTaskItem $task)
	{
		$taskId = $task->getId();
		$userId = CurrentUser::get()->getId();

		if (!$task->checkCanRead())
		{
			return;
		}

		$list = ViewedTable::getList([
			'select' => ['TASK_ID', 'USER_ID'],
			'filter' => [
				'=TASK_ID' => $taskId,
				'=USER_ID' => $userId,
			],
		]);

		if ($item = $list->fetch())
		{
			ViewedTable::update($item, ['VIEWED_DATE' => new DateTime()]);
		}
		else
		{
			ViewedTable::add([
				'TASK_ID' => $taskId,
				'USER_ID' => $userId,
				'VIEWED_DATE' => new DateTime(),
			]);
		}

		$event = new Event('tasks', 'onTaskUpdateViewed', ['taskId' => $taskId, 'userId' => $userId]);
		$event->send();
	}
}