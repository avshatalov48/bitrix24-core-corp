<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\Stakeholder;

use Bitrix\Tasks\V2\Public\Command\Task\Stakeholder\DelegateCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Responsible\Permission;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;

class Responsible extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Stakeholder.Responsible.delegate
	 */
	public function delegateAction(
		#[Permission\Delegate]
		Entity\Task $task,
	): ?Entity\EntityInterface
	{
		$result = (new DelegateCommand(
			taskId: $task->getId(),
			responsibleId: (int)$task->responsible?->getId(),
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