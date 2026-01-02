<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Relation;

use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\ParentService;
use Bitrix\Tasks\V2\Internal\Entity;

class DeleteParentRelationHandler
{
	public function __construct(
		private readonly ParentService $parentService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{
	}

	public function __invoke(DeleteParentRelationCommand $command): Entity\Task
	{
		return $this->consistencyResolver->resolve('task.parent.delete')->wrap(
			fn (): Entity\Task => $this->parentService->deleteParent($command->taskId, $command->userId),
		);
	}
}
