<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Onboarding\Event;

use Bitrix\Main\EventResult;
use Bitrix\Tasks\Internals\Task\Status;
use Bitrix\Tasks\Onboarding\Internal\Factory\CommandModelFactory;
use Bitrix\Tasks\Onboarding\Internal\Type;
use Bitrix\Tasks\Onboarding\OnboardingFeature;
use Bitrix\Tasks\Onboarding\Transfer\CommandModelCollection;

final class AddTaskEventListener extends AbstractEventListener
{
	public function onTaskAdd(int $taskId, array $fields): EventResult
	{
		$eventResult = new EventResult(EventResult::SUCCESS);

		$createdBy = (int)$fields['CREATED_BY'];
		$responsibleId = (int)$fields['RESPONSIBLE_ID'];

		$commandModels = new CommandModelCollection();

		$counterRepository = $this->container->getCounterRepository();
		if (!$counterRepository->isLimitReachedByType(Type::InviteToMobile, $createdBy))
		{
			$commandModels->merge($this->getCommandsForInvitationToMobile($taskId, $createdBy));
		}

		$status = (int)($fields['STATUS'] ?? 0);
		if (
			$status !== Status::PENDING
			|| $this->isOnePersonTask($fields)
		)
		{
			$this->saveCommandModels($commandModels);

			return $eventResult;
		}

		if ($this->isInvitedUser($responsibleId))
		{
			$commandModels->merge($this->getCommandsForInvitedResponsible($taskId, $responsibleId));
		}
		elseif (OnboardingFeature::isNewPortal())
		{
			$commandModels->merge($this->getCommandsForExistingResponsible($taskId, $responsibleId, $createdBy));
		}

		$this->saveCommandModels($commandModels);

		return $eventResult;
	}

	private function getCommandsForInvitationToMobile(int $taskId, int $creatorId): CommandModelCollection
	{
		return new CommandModelCollection(
			CommandModelFactory::create(Type::InviteToMobile, $taskId, $creatorId, true),
		);
	}

	private function getCommandsForInvitedResponsible(int $taskId, int $responsibleId): CommandModelCollection
	{
		return new CommandModelCollection(
			CommandModelFactory::create(Type::ResponsibleInvitationNotAcceptedOneDay, $taskId, $responsibleId),
		);
	}

	private function getCommandsForExistingResponsible(
		int $taskId,
		int $responsibleId,
		int $createdBy,
	): CommandModelCollection
	{
		return new CommandModelCollection(
			CommandModelFactory::create(Type::OneDayNotViewed, $taskId, $responsibleId, true),
			CommandModelFactory::create(Type::TwoDaysNotViewed, $taskId, $createdBy, true),
		);
	}
}