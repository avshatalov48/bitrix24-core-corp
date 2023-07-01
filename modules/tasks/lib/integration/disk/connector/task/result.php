<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Integration\Disk\Connector\Task;

use Bitrix\Disk\Uf\StubConnector;
use Bitrix\Tasks\Access\ActionDictionary;
use Bitrix\Tasks\Internals\Task\Result\ResultTable;

class Result extends StubConnector
{
	public $canRead;

	public function canRead($userId)
	{
		if($this->canRead !== null)
		{
			return $this->canRead;
		}

		$taskId = $this->getTaskId();
		if (!$taskId)
		{
			return false;
		}

		return \Bitrix\Tasks\Access\TaskAccessController::can((int) $userId, ActionDictionary::ACTION_TASK_READ, $taskId);
	}

	public function canUpdate($userId)
	{
		return false;
	}

	/**
	 * @return int|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function getTaskId(): ?int
	{
		$resultId = (int) $this->entityId;
		$result = ResultTable::getById($resultId)->fetchObject();

		if (!$result)
		{
			return null;
		}

		return $result->getTaskId();
	}
}