<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;
use Bitrix\Tasks\Onboarding\Internal\Config\TaskCountLimit;
use Bitrix\Tasks\Onboarding\Internal\Factory\CommandModelFactory;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\OnboardingFeature;
use Bitrix\Tasks\Onboarding\Transfer\CommandModelCollection;

final class ExpiredSoonEventListener extends AbstractEventListener
{
	public function onTaskExpiredSoon(int $taskId, array $task): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		if (!OnboardingFeature::isNewPortal())
		{
			return $eventResult;
		}

		$responsibleId = (int)($task['RESPONSIBLE_ID'] ?? 0);
		if (!$this->isOnePersonTask($task) || $this->isInvitedUser($responsibleId))
		{
			return $eventResult;
		}

		$createdBy = (int)($task['CREATED_BY'] ?? 0);

		$counterRepository = $this->container->getCounterRepository();

		// this check is also in query service, but we do it here to avoid the next count query
		if ($counterRepository->isLimitReachedByType(Type::TooManyTasks, $createdBy))
		{
			return $eventResult;
		}

		$limit = TaskCountLimit::get(Type::TooManyTasks);

		$taskRepository = $this->container->getTaskRepository();
		$count = $taskRepository->getOnePersonTasksCount($createdBy);
		if ($count < $limit)
		{
			return $eventResult;
		}

		$commandModels = new CommandModelCollection(
			CommandModelFactory::create(Type::TooManyTasks, $taskId, $createdBy, true)
		);

		$this->saveCommandModels($commandModels);

		return $eventResult;
	}
}