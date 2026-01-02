<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Public\Command\Task\Tracking;

use Bitrix\Tasks\V2\Internal\Entity\Task\Timer;
use Bitrix\Tasks\V2\Internal\Service\Consistency\ConsistencyResolverInterface;
use Bitrix\Tasks\V2\Internal\Service\Task\TimeManagementService;

class StopTimerHandler
{
	public function __construct(
		private readonly TimeManagementService $timeManagementService,
		private readonly ConsistencyResolverInterface $consistencyResolver,
	)
	{

	}

	public function __invoke(StopTimerCommand $command): ?Timer
	{
		return $this->consistencyResolver->resolve('task.time.stop')->wrap(
			fn (): ?Timer => $this->timeManagementService->stopTimer(
				userId: $command->userId,
				taskId: $command->taskId,
			)
		);
	}
}