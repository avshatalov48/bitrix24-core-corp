<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;
use Bitrix\Tasks\Internals\Registry\TaskRegistry;
use Bitrix\Tasks\Onboarding\Internal\Factory\CommandModelFactory;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\OnboardingFeature;
use Bitrix\Tasks\Onboarding\Transfer\CommandModelCollection;

final class UpdateTaskEventListener extends AbstractEventListener
{
	public function onTaskUpdate(int $taskId, array $changedFields, array $previousFields): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		if (
			!$this->isResponsibleChanged($changedFields, $previousFields)
			&& !$this->isCreatorChanged($changedFields, $previousFields)
		)
		{
			return $eventResult;
		}

		$task = TaskRegistry::getInstance()->getObject($taskId);
		if ($task === null)
		{
			return $eventResult;
		}

		$deleteResult = $this->deleteByPair($taskId);
		if (!$deleteResult->isSuccess())
		{
			return $eventResult;
		}

		if ($this->isOnePersonTask($task))
		{
			return $eventResult;
		}

		if (!$task->isPending())
		{
			return $eventResult;
		}

		if ($this->isInvitedUser($task->getResponsibleId()))
		{
			return $eventResult;
		}

		if ($this->isTaskViewed($taskId, $task->getResponsibleId()))
		{
			return $eventResult;
		}

		if (!OnboardingFeature::isNewPortal())
		{
			return $eventResult;
		}

		$commandModels = new CommandModelCollection(
			CommandModelFactory::create(Type::OneDayNotViewed, $taskId, $task->getResponsibleId(), true),
			CommandModelFactory::create(Type::TwoDaysNotViewed, $taskId, $task->getCreatedBy(), true)
		);

		$this->saveCommandModels($commandModels);

		return new EventResult(EventResult::SUCCESS);
	}

	private function isResponsibleChanged(array $changedFields, array $previousFields): bool
	{
		if (!isset($changedFields['RESPONSIBLE_ID']))
		{
			return false;
		}

		$previousResponsibleId = (int)($previousFields['RESPONSIBLE_ID'] ?? null);
		$newResponsibleId = (int)$changedFields['RESPONSIBLE_ID'];

		return $previousResponsibleId !== $newResponsibleId;
	}

	private function isCreatorChanged(array $changedFields, array $previousFields): bool
	{
		if (!isset($changedFields['CREATED_BY']))
		{
			return false;
		}

		$previousCreatedBy = (int)($previousFields['CREATED_BY'] ?? null);
		$newCreatedBy = (int)$changedFields['CREATED_BY'];

		return $previousCreatedBy !== $newCreatedBy;
	}
}