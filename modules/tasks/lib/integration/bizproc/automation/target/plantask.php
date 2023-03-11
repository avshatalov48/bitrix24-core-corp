<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Target;

use Bitrix\Tasks;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Bizproc\Document;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class PlanTask extends Base
{
	protected $entityStages;

	public function getDocumentStatus()
	{
		$planId = Document\Task::resolvePlanId($this->getDocumentType()[2]);

		$result = Tasks\Kanban\TaskStageTable::getList(array(
			'select' => ['STAGE_ID'],
			'filter' => array(
				'TASK_ID' => $this->getDocumentId(),
				'=STAGE.ENTITY_TYPE' => Tasks\Kanban\StagesTable::WORK_MODE_USER,
				'STAGE.ENTITY_ID' => $planId
			)
		))->fetch();

		if (!$result)
		{
			Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_USER);
			return Tasks\Kanban\StagesTable::getDefaultStageId($planId);
		}

		return $result['STAGE_ID'];
	}

	public function setDocumentStatus($statusId)
	{
		$planId = Document\Task::resolvePlanId($this->getDocumentType()[2]);

		$stages = Tasks\Kanban\TaskStageTable::getList(array(
			'select' => ['ID', 'STAGE_ID'],
			'filter' => array(
				'=TASK_ID' => $this->getDocumentId(),
				'=STAGE.ENTITY_TYPE' => Tasks\Kanban\StagesTable::WORK_MODE_USER,
				'=STAGE.ENTITY_ID' => $planId
			)
		))->fetchAll();

		if (in_array($statusId, array_column($stages, 'STAGE_ID')))
		{
			return;
		}

		foreach ($stages as $stage)
		{
			Tasks\Kanban\TaskStageTable::update($stage['ID'], ['STAGE_ID' => $statusId]);

			Tasks\Integration\Bizproc\Listener::onPlanTaskStageUpdate(
				$planId,
				$this->getDocumentId(),
				$statusId
			);
		}
	}

	public function getDocumentStatusList($categoryId = 0)
	{
		$planId = Document\Task::resolvePlanId($this->getDocumentType()[2]);

		$previousWorkMode = Tasks\Kanban\StagesTable::getWorkMode();

		Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_USER);
		$stages = Tasks\Kanban\StagesTable::getStages($planId);

		Tasks\Kanban\StagesTable::setWorkMode($previousWorkMode);

		return $stages;
	}
}