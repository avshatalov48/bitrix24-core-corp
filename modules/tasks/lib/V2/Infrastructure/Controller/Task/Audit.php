<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Tasks\V2\Internal\Access\Task\Permission;
use Bitrix\Tasks\V2\Public\Command\Task\Audit\UnwatchTaskCommand;
use Bitrix\Tasks\V2\Public\Command\Task\Audit\WatchTaskCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;

class Audit extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Audit.watch
	 */
	public function watchAction(
		#[Permission\Read]
		Entity\Task $task
	): ?bool
	{
		$result = (new WatchTaskCommand(
			taskId: $task->getId(),
			userId: $this->userId,
			auditorId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}

	/**
	 * @ajaxAction tasks.V2.Task.Audit.unwatch
	 */
	public function unwatchAction(
		#[Permission\Read]
		Entity\Task $task
	): ?bool
	{
		$result = (new UnwatchTaskCommand(
			taskId: $task->getId(),
			userId: $this->userId,
			auditorId: $this->userId,
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return true;
	}
}
