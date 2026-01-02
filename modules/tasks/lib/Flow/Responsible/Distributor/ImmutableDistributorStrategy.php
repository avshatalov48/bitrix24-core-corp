<?php

namespace Bitrix\Tasks\Flow\Responsible\Distributor;

use Bitrix\Tasks\Flow\Control\Trait\ActiveUserOrAdminTrait;
use Bitrix\Tasks\Flow\Flow;
use Bitrix\Tasks\Flow\Task\Trait\TaskFlowTrait;
use Bitrix\Tasks\Util\User;

class ImmutableDistributorStrategy implements DistributorStrategyInterface
{
	use TaskFlowTrait;
	use ActiveUserOrAdminTrait;

	public function distribute(Flow $flow, array $fields, array $taskData): int
	{
		$isTaskAddedToFlow = $this->isTaskAddedToFlow($fields, $taskData);
		$isTaskStatusNew = $this->isTaskStatusNew($taskData);

		if (empty($taskData) || ($isTaskAddedToFlow && $isTaskStatusNew))
		{
			$responsibleId = $fields['RESPONSIBLE_ID'] ?? 0;

			if (
				$responsibleId > 0
				&& $responsibleId !== $fields['CREATED_BY']
				&& $this->isActiveUser($responsibleId)
				&& !$this->isUserAbsent($responsibleId)
			)
			{
				return (int)$fields['RESPONSIBLE_ID'];
			}

			$flowOwnerId = $flow->getOwnerId();

			if (
				$flowOwnerId > 0
				&& $this->isActiveUser($flowOwnerId)
				&& !$this->isUserAbsent($flowOwnerId)
			)
			{
				return $flowOwnerId;
			}

			return $this->getActiveUserOrAdminId($flow->getCreatorId());
		}

		$responsibleId = $fields['RESPONSIBLE_ID'] ?? $taskData['RESPONSIBLE_ID'];

		return (int)$responsibleId;
	}

	private function isActiveUser(int $userId): bool
	{
		return User::isActive($userId);
	}

	private function isUserAbsent(int $userId): bool
	{
		return !empty(User::isAbsence([$userId]));
	}
}