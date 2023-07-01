<?php
namespace Bitrix\Tasks\Integration\Bizproc\Automation\Target;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

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
			\CTasks::STATE_PENDING => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_PENDING')
			],
			\CTasks::STATE_IN_PROGRESS => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_IN_PROGRESS')
			],
			\CTasks::STATE_SUPPOSEDLY_COMPLETED => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_SUPPOSEDLY_COMPLETED')
			],
			\CTasks::STATE_COMPLETED => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_COMPLETED')
			],
			\CTasks::STATE_DEFERRED => [
				'TITLE' => Loc::getMessage('TASKS_BP_AUTOMATION_PERSONAL_STATUS_DEFERRED')
			],
		];
	}
}