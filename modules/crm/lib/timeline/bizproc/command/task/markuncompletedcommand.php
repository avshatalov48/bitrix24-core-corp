<?php

namespace Bitrix\Crm\Timeline\Bizproc\Command\Task;

use Bitrix\Crm\Service\Timeline\Item\Activity\Bizproc;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

class MarkUnCompletedCommand extends MarkCompletedCommand
{
	protected string $status = Bizproc\Task::TASK_STATUS_RUNNING;

	public function execute(): Result
	{
		$result = new Result();

		$find = false;
		foreach ($this->findActivities() as $activity)
		{
			$settings = $activity['SETTINGS'] ?? null;
			if (!$settings)
			{
				continue;
			}

			$status = $settings['STATUS'] ?? Bizproc\Task::TASK_STATUS_RUNNING;
			if (!$this->canChangeStatus($status))
			{
				continue;
			}

			$settings['STATUS'] = $this->status;

			$find = true;
			if (!\CCrmActivity::Update($activity['ID'], ['SETTINGS' => $settings]))
			{
				foreach (\CCrmActivity::GetErrorMessages() as $errorMessage)
				{
					$result->addError(new Error($errorMessage));
				}

				break;
			}
		}

		$result->setData(['find' => $find]);

		return $result;
	}

	protected function canChangeStatus(string $status): bool
	{
		return $status === Bizproc\Task::TASK_STATUS_DONE;
	}
}
