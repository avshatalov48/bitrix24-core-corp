<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Gantt;

use Bitrix\Tasks\V2\Internal\Entity\Task\GanttLink;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\Gantt\GanttDependenceService;

class AddDependenceHandler
{
	public function __construct(
		private readonly GanttDependenceService $ganttDependenceService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{
	}

	public function __invoke(AddDependenceCommand $command): void
	{
		$entity = new GanttLink(
			taskId: $command->taskId,
			dependentId: $command->dependentId,
			type: $command->linkType,
			creatorId: $command->userId
		);

		$this->consistencyResolver->resolve('task.gantt.add.dependence')->wrap(
			fn() => $this->ganttDependenceService->add($entity)
		);
	}
}
