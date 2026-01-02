<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\Stakeholder;

use Bitrix\Tasks\V2\Public\Command\Task\Stakeholder\SetAccomplicesCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Accomplice\Permission;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;

class Accomplice extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Stakeholder.Accomplice.set
	 */
	public function setAction(
		#[Permission\Update]
		Entity\Task $task,
	): ?Entity\EntityInterface
	{
		$result = (new SetAccomplicesCommand(
			taskId: $task->getId(),
			accompliceIds: (array)$task->accomplices?->getIdList(),
			config: new UpdateConfig($this->userId),
		))->run();

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());

			return null;
		}

		return $result->getObject();
	}
}