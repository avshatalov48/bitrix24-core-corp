<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Tracking;

use Bitrix\Tasks\V2\Internal\Entity\Task\Timer;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\TimeManagementService;

class StartTimerHandler
{
	public function __construct(
		private readonly TimeManagementService $timeManagementService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{

	}
	public function __invoke(StartTimerCommand $command): Timer
	{
		return $this->consistencyResolver->resolve('task.time.start')->wrap(
			fn (): Timer => $this->timeManagementService->startTimer(
				userId: $command->userId,
				taskId: $command->taskId,
				syncPlan: $command->syncPlan,
				canStart: $command->canStart,
				canRenew: $command->canRenew,
			)
		);
	}
}
