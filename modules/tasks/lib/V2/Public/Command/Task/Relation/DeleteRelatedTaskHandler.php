<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Relation;

use Bitrix\Tasks\V2\Internal\Entity\Task;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\RelatedTaskService;

class DeleteRelatedTaskHandler
{
	public function __construct(
		private readonly RelatedTaskService $relatedTaskService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{
	}

	public function __invoke(DeleteRelatedTaskCommand $command): Task
	{
		return $this->consistencyResolver->resolve('task.related.delete')->wrap(
			fn (): Task => $this->relatedTaskService->delete($command->taskId, [$command->relatedTaskId], $command->userId),
		);
	}
}

