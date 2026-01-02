<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Attention;

use Bitrix\Tasks\V2\Internal\Service\Task\Action\Update\Config\UpdateConfig;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Service\UpdateTaskService;

class SetHighTaskPriorityHandler
{
	public function __construct(
		private readonly UpdateTaskService $updateTaskService,
	)
	{

	}

	public function __invoke(SetHighTaskPriorityCommand $command): Entity\Task
	{
		$entity = new Entity\Task(
			id: $command->taskId,
			priority: Entity\Task\Priority::High,
		);

		$config = new UpdateConfig(
			userId: $command->userId,
		);


		return $this->updateTaskService->update(
			task: $entity,
			config: $config,
		);
	}
}
