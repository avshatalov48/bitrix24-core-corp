<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Target;

use Bitrix\Tasks;
use Bitrix\Main\Loader;
use Bitrix\Tasks\Integration\Bizproc\Document;

if (!Loader::includeModule('bizproc'))
{
	return;
}

class ProjectTask extends Base
{
	protected $entityStages;

	public function getDocumentStatus()
	{
		$projectId = Document\Task::resolveProjectId($this->getDocumentType()[2]);
		$fields = $this->getFields();
		$documentStatus = (int) ($fields['STAGE_ID'] ?? 0);
		if ($documentStatus === 0 && $projectId > 0)
		{
			Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_GROUP);
			$documentStatus = Tasks\Kanban\StagesTable::getDefaultStageId($projectId);
		}

		return $documentStatus;
	}

	public function setDocumentStatus($statusId)
	{
		$task = new \CTasks();

		$result = $task->update($this->getDocumentId(), ['STAGE_ID' => $statusId]);

		if ($result)
		{
			$this->setField('STAGE_ID', $statusId);
		}
	}

	public function getDocumentStatusList($categoryId = 0)
	{
		$projectId = Document\Task::resolveProjectId($this->getDocumentType()[2]);
		Tasks\Kanban\StagesTable::setWorkMode(Tasks\Kanban\StagesTable::WORK_MODE_GROUP);
		return Tasks\Kanban\StagesTable::getStages($projectId);
	}
}