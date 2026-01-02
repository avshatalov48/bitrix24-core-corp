<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Service\Task;

use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\TimerRepositoryInterface;

class TimerService
{
	public function __construct(
		private readonly TimerRepositoryInterface $timerRepository
	)
	{

	}

	public function start(int $userId, int $taskId): Entity\Task\Timer
	{
		$timer = new Entity\Task\Timer(
			userId: $userId,
			taskId: $taskId,
			startedAtTs: time(),
			seconds: 0
		);

		$this->timerRepository->upsert($timer);

		return $timer;
	}

	public function stop(int $userId, int $taskId): ?Entity\Task\Timer
	{
		$timer = $this->timerRepository->get($userId, $taskId);
		if ($timer === null)
		{
			return null;
		}

		if ((int)$timer->startedAtTs === 0 && (int)$timer->seconds === 0)
		{
			return null;
		}

		$newTimer = $timer->cloneWith([
			'startedAtTs' => 0,
			'seconds' => 0,
		]);

		$this->timerRepository->update($newTimer);

		$seconds = time() - $timer->startedAtTs;
		if ($timer->startedAtTs > 0 && $seconds > 0)
		{
			$timer = $timer->cloneWith([
				'seconds' => (int)$timer->seconds + $seconds,
			]);
		}

		return $timer;
	}
}