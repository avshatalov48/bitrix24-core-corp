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
			if ($status !== Bizproc\Task::TASK_STATUS_RUNNING)
			{
				continue;
			}

			unset($settings['IS_INLINE'], $settings['BUTTONS']);
			$settings['STATUS'] = $this->status;

			$updateFields['SETTINGS'] = $settings;
			if (Loader::includeModule('bizproc'))
			{
				$manager = new Manager();
				$isWaitForClosure = $manager->getControlValue($manager::WAIT_FOR_CLOSURE_TASK_OPTION) === 'Y';
				$updateFields['COMPLETED'] = $isWaitForClosure ? 'N' : 'Y';
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

	protected function findActivities(): array
	{
		return (new Task())->find($this->task->id, $this->responsibleId);
	}
}
