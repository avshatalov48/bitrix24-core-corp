<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\Tracking;

use Bitrix\Tasks\V2\Internal\Access\Factory\AccessControllerTrait;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Internal\Access\Task\Tracking;
use Bitrix\Tasks\V2\Public\Command\Task\Tracking\AddElapsedTimeCommand;

class ElapsedTime extends BaseController
{
	use AccessControllerTrait;

	/**
	 * @ajaxAction tasks.V2.Task.Tracking.ElapsedTime.add
	 */
	public function addAction(
		#[Permission\Read]
		#[Tracking\Permission\ElapsedTime]
		Entity\Task $task,
	): ?Entity\EntityInterface
	{
		$result = (new AddElapsedTimeCommand(
			elapsedTime: Entity\Task\ElapsedTime::mapFromArray([
				...$task->elapsedTime->toArray(),
				'userId' => $this->userId,
				'taskId' => $task->getId(),
				'createdAtTs' => time(),
			]),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getObject();
	}
}
