<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Task\Stakeholder;

use Bitrix\Tasks\V2\Public\Command\Task\Stakeholder\SetAuditorsCommand;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Task\Auditor\Permission;
use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;

class Auditor extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Task.Stakeholder.Auditor.set
	 */
	public function setAction(
		#[Permission\Update]
		Entity\Task $task,
	): ?Entity\EntityInterface
	{
		$result = (new SetAuditorsCommand(
			taskId: $task->getId(),
			auditorIds: (array)$task->auditors?->getIdList(),
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