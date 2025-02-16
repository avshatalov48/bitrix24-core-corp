<?php

namespace Bitrix\Crm\Timeline\Bizproc\Command\Task;

use Bitrix\Crm\Activity\Provider\Bizproc\Task;
use Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;
use Bitrix\Crm\Timeline\Bizproc\Data\Workflow;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Bizproc\Integration\Intranet\Settings\Manager;

class MarkCompletedCommand
{
	protected \Bitrix\Crm\Timeline\Bizproc\Data\Task $task;
	protected int $responsibleId = 0;
	protected string $status = Bizproc\Task::TASK_STATUS_DONE;

	public function __construct(\Bitrix\Crm\Timeline\Bizproc\Data\Task $task, int $responsibleId = 0)
	{
		$this->task = $task;
		$this->responsibleId = max($responsibleId, 0);
	}

	public function execute(): Result
	{
		$result = new Result();

		foreach ($this->findActivities() as $activity)
		{
			$settings = $activity['SETTINGS'] ?? null;
			if (!$settings)
			{
				continue;
			}

			$status = $settings['STATUS'] ?? Bizproc\Task::TASK_STATUS_DONE;
			if (!$this->canChangeStatus($status))
			{
				continue;
			}

			$settings['STATUS'] = $this->status;

			$updateFields['SETTINGS'] = $settings;
			$isWaitForClosure = $this->isWaitForClosure();
			if (!$isWaitForClosure)
			{
				$updateFields['COMPLETED'] = 'Y';
			}

			if (!\CCrmActivity::Update($activity['ID'], $updateFields))
			{
				foreach (\CCrmActivity::GetErrorMessages() as $errorMessage)
				{
					$result->addError(new Error($errorMessage));
				}

				break;
			}
		}

		return $result;
	}

	protected function canChangeStatus(string $status): bool
	{
		return $status === Bizproc\Task::TASK_STATUS_RUNNING;
	}

	protected function findActivities(): array
	{
		return (new Task())->find($this->task->id, $this->responsibleId);
	}

	protected function isWaitForClosure(): bool
	{
		if (Loader::includeModule('bizproc'))
		{
			$manager = new Manager();

			return $manager->getControlValue($manager::WAIT_FOR_CLOSURE_TASK_OPTION) === 'Y';
		}

		return true;
	}
}
