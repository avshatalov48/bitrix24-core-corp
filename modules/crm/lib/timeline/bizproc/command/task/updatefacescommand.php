<?php

namespace Bitrix\Crm\Timeline\Bizproc\Command\Task;

use Bitrix\Crm\Activity\Provider\Bizproc\Task;
use Bitrix\Crm\Timeline\Bizproc\Data\Workflow;
use Bitrix\Main\Error;
use Bitrix\Main\Result;

final class UpdateFacesCommand
{
	private Workflow $workflow;

	public function __construct(Workflow $workflow)
	{
		$this->workflow = $workflow;
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

			$activityId = $activity['ID'];
			$activity = \Bitrix\Crm\Service\Container::getInstance()->getActivityBroker()->getById($activityId);
			if (!$activity)
			{
				continue;
			}

			$activityController = \Bitrix\Crm\Timeline\ActivityController::getInstance();
			$activityController->notifyTimelinesAboutActivityUpdate($activity);
		}

		return $result;
	}

	private function findActivities(): array
	{
		return (new Task())->findByWorkflowId($this->workflow->id);
	}
}
