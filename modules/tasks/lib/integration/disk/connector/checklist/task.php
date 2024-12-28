<?php
namespace Bitrix\Tasks\Integration\Disk\Connector\CheckList;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\Disk\Connector\Task as TaskConnector;
use Bitrix\Tasks\Internals\Task\CheckListTable;
use Bitrix\Tasks\Access\TaskAccessController;
use Bitrix\Tasks\Access\ActionDictionary;

use Bitrix\Main\Localization\Loc;
use CTaskItem;
use TasksException;

Loc::loadMessages(__FILE__);

/**
 * Class Task
 *
 * @package Bitrix\Tasks\Integration\Disk\Connector\CheckList\Task
 */
class Task extends TaskConnector
{
	/**
	 * @return string
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function getTitle()
	{
		$taskId = static::getTaskIdByCheckList($this->entityId);
		return Loc::getMessage('DISK_UF_CHECKLIST_TASK_CONNECTOR_TITLE', ['#ID#' => $taskId]);
	}

	/**
	 * @param $userId
	 * @return array|bool|mixed|null
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	protected function loadTaskData($userId)
	{
		if ($this->taskPostData === null)
		{
			try
			{
				$taskId = static::getTaskIdByCheckList($this->entityId);

				$task = CTaskItem::getInstance($taskId, $userId);
				$this->taskPostData = $task->getData(false);
			}
			catch (TasksException $e)
			{
				return [];
			}
		}

		return $this->taskPostData;
	}

	public function canRead($userId): bool
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		$taskId = static::getTaskIdByCheckList($this->entityId);

		$this->canRead = TaskAccessController::can($userId, ActionDictionary::ACTION_TASK_READ, $taskId);

		return $this->canRead;
	}

	public function canUpdate($userId): bool
	{
		return $this->canRead($userId);
	}

	/**
	 * @param $checkListId
	 * @return mixed
	 * @throws ArgumentException
	 * @throws ObjectPropertyException
	 * @throws SystemException
	 */
	private static function getTaskIdByCheckList($checkListId)
	{
		return CheckListTable::getList(['select' => ['TASK_ID'], 'filter' => ['ID' => $checkListId]])->fetch()['TASK_ID'];
	}

	/**
	 * @param $authorId
	 * @param array $data
	 */
	public function addComment($authorId, array $data)
	{
		return;
	}
}
