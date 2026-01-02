<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Gantt;

use Bitrix\Tasks\V2\Internal\Entity\Task\GanttLink;
use Bitrix\Tasks\V2\Internal\Service\Task\Gantt\GanttDependenceService;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;

class DeleteDependenceHandler
{
	public function __construct(
		private readonly GanttDependenceService $ganttDependenceService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{
	}

	public function __invoke(DeleteDependenceCommand $command): void
	{
		$entity = new GanttLink(
			taskId: $command->taskId,
			dependentId: $command->dependentId,
		);

		$this->consistencyResolver->resolve('task.gantt.delete.dependence')->wrap(
			fn() => $this->ganttDependenceService->delete($entity)
		);
	}
}
