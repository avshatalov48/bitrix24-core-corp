<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Relation;

use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\ParentService;
use Bitrix\Tasks\V2\Internal\Entity;

class SetParentRelationHandler
{
	public function __construct(
		private readonly ParentService $parentService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{
	}

	public function __invoke(SetParentRelationCommand $command): Entity\Task
	{
		return $this->consistencyResolver->resolve('task.parent.set')->wrap(
			fn (): Entity\Task => $this->parentService->setParent($command->taskId, $command->parentId, $command->userId),
		);
	}
}
