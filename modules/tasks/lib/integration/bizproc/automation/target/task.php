<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Target;

use Bitrix\Tasks;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Bizproc\Automation\Factory;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class Task extends Base
{
	protected $entityStages;

	public function isAvailable()
	{
		return Factory::isAutomationAvailable(static::getEntityTypeId());
	}

	public function getEntityId()
	{
		$entity = $this->getEntity();
		return isset($entity['ID']) ? (int)$entity['ID'] : 0;
	}

	public function setEntityById($id)
	{
		$id = (int)$id;
		if ($id > 0)
		{
			//$task = Tasks\Item\Task::getInstance($id, 1);
			//$fields = $task->getData();
			$itemIterator = \CTasks::getByID($id, false);
			$fields = $itemIterator->fetch();

			if ($fields)
			{
				$this->setEntity($fields);
				$this->setDocumentId($id);
			}
		}
	}

	public function getEntity()
	{
		if ($this->entity === null && $id = $this->getDocumentId())
		{
			$this->setEntityById($id);
		}

		return parent::getEntity();
	}

	public function getEntityStatus()
	{
		$entity = $this->getEntity();
		$stageId = (int) $entity['STAGE_ID'];
		if ($stageId === 0 && $entity['GROUP_ID'] > 0)
		{
			Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_GROUP);
			$stageId = Tasks\Kanban\StagesTable::getDefaultStageId($entity['GROUP_ID']);
		}

		return $stageId;
	}

	public function setEntityStatus($statusId)
	{
		$id = $this->getEntityId();

		$fields = array('STAGE_ID' => $statusId);


		$task = new \CTasks();
		$result = $task->update($id, $fields);

		if ($result)
		{
			$this->setEntityField('STAGE_ID', $statusId);
		}
	}

	public function getEntityStatuses()
	{
		if ($this->entityStages === null)
		{
			$entity = $this->getEntity();
			$categoryId = isset($entity['GROUP_ID']) ? (int)$entity['GROUP_ID'] : 0;

			//I can`t believe it`s true...
			Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_GROUP);
			$stages = Tasks\Kanban\StagesTable::getStages($categoryId);

			$this->entityStages = array_keys($stages);
		}

		return $this->entityStages;
	}

	public function getStatusInfos($categoryId = 0)
	{
		Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_GROUP);
		return Tasks\Kanban\StagesTable::getStages($categoryId);
	}
}