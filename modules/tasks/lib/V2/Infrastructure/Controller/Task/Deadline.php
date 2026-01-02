<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task;

use Bitrix\Main\Error;
use Bitrix\Tasks\V2\Internal\Access\Task\Deadline\Permission;
use Bitrix\Tasks\V2\Public\Command\Task\Deadline\UpdateDeadlineCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;

class Deadline extends BaseController
{
	/**
	 * @ajaxAction tasks.v2.Task.Deadline.update
	 */
	public function updateAction(
		#[Permission\Update]
		Entity\Task $task
	): ?Entity\EntityInterface
	{
		$result = (new UpdateDeadlineCommand(
			taskId: $task->getId(),
			deadlineTs: $task->deadlineTs,
			updateConfig: new UpdateConfig($this->userId))
		)->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getObject();
	}
}
