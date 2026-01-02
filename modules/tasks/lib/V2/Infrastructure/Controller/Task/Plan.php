<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Tasks\V2\Public\Command\Task\Plan\UpdatePlanCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Access\Task\Plan\Permission;

class Plan extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Plan.update
	 */
	public function updateAction(
		#[Permission\Update]
		Entity\Task $task,
	): ?Entity\EntityInterface
	{
		$result = (new UpdatePlanCommand(
			taskId: $task->getId(),
			config: new UpdateConfig($this->userId),
			startPlanTs: $task->startPlanTs,
			endPlanTs: $task->endPlanTs,
			duration: $task->plannedDuration,
			matchesWorkTime: $task->matchesWorkTime,
			matchesSubTasksTime: $task->matchesSubTasksTime,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getObject();
	}
}
