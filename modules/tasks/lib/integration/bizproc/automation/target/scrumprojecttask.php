<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Target;

use Bitrix\Main\Loader;
use Bitrix\Tasks;
use Bitrix\Tasks\Integration\Bizproc\Document;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class ScrumProjectTask extends Base
{
	protected $entityStages;

	public function getDocumentStatus()
	{
		if ($taskStage = $this->getTaskStage())
		{
			$documentStatus = $taskStage['STAGE_ID'];
		}
		else
		{
			$kanbanService = new Tasks\Scrum\Service\KanbanService();

			$projectId = Document\Task::resolveScrumProjectId($this->getDocumentType()[2]);

			$sprintService = new Tasks\Scrum\Service\SprintService();

			$sprint = $sprintService->getActiveSprintByGroupId($projectId);

			if ($kanbanService->isTaskInKanban($sprint->getId(), $this->getDocumentId()))
			{
				Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_ACTIVE_SPRINT);

				$documentStatus = Tasks\Kanban\StagesTable::getDefaultStageId($sprint->getId());
			}
			else
			{
				return 0;
			}
		}

		return $documentStatus;
	}

	public function setDocumentStatus($statusId)
	{
		if ($taskStage = $this->getTaskStage())
		{
			Tasks\Kanban\TaskStageTable::update($taskStage['ID'], [
				'STAGE_ID' => $statusId,
			]);

			$task = new \CTasks();
			$result = $task->update($this->getDocumentId(), ['STAGE_ID' => $statusId]);
			if ($result)
			{
				$this->setField('STAGE_ID', $statusId);
			}
		}
	}

	public function getDocumentStatusList($categoryId = 0)
	{
		$sprintService = new Tasks\Scrum\Service\SprintService();
		$kanbanService = new Tasks\Scrum\Service\KanbanService();

		$projectId = Document\Task::resolveScrumProjectId($this->getDocumentType()[2]);
		$sprint = $sprintService->getActiveSprintByGroupId($projectId);

		Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_ACTIVE_SPRINT);

		return $kanbanService->getStages($sprint->getId());
	}

	private function getTaskStage(): array
	{
		$kanbanService = new Tasks\Scrum\Service\KanbanService();

		$queryObject = Tasks\Kanban\TaskStageTable::getList([
			'filter' => [
				'TASK_ID' => $this->getDocumentId(),
				'=STAGE.ENTITY_TYPE' => Tasks\Kanban\StagesTable::WORK_MODE_ACTIVE_SPRINT,
				'STAGE.ENTITY_ID' => $kanbanService->getTaskEntityId($this->getDocumentId())
			]
		]);

		if ($taskStage = $queryObject->fetch())
		{
			return $taskStage;
		}
		else
		{
			return [];
		}
	}
}