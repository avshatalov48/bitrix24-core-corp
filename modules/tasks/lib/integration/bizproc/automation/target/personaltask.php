<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Target;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;

if (!Loader::includeModule('bizproc'))
{
	return;
}

Loc::loadMessages(__FILE__);

class PersonalTask extends Base
{
	protected $entityStages;

	public function getDocumentStatus()
	{
		$entity = $this->getFields();
		return (int)($entity['STATUS'] ?? null);
	}

	public function setDocumentStatus($statusId)
	{
		$id = $this->getDocumentId();

		$task = new \CTasks();
		$result = $task->update($id, ['STATUS' => $statusId]);

		if ($result)
		{
			$this->setField('STATUS', $statusId);
		}
	}

	public function getDocumentStatusList($categoryId = 0)
	{
		return [
			Status::PENDING => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_PENDING')
			],
			Status::IN_PROGRESS => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_IN_PROGRESS')
			],
			Status::SUPPOSEDLY_COMPLETED => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_SUPPOSEDLY_COMPLETED')
			],
			Status::COMPLETED => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_COMPLETED')
			],
			Status::DEFERRED => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_DEFERRED')
			],
		];
	}
}