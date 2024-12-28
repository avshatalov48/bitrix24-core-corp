<?php

namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Flow\FlowRegistry;
use Bitrix\Tasks\Access\AccessibleTask;
use Bitrix\Main\Loader;

trait FlowTrait
{
	private function checkFlowPermissions(int $flowId): bool
	{
		$flow = FlowRegistry::getInstance()->get($flowId, ['*', 'MEMBERS']);
		if (null === $flow)
		{
			return false;
		}

		$flowModel = FlowModel::createFromArray($flow->toArray());

		if ($flowModel->isForAll())
		{
			return true;
		}

		if ($flowModel->isUserMember($this->user->getUserId()))
		{
			return true;
		}

		if (!empty(array_intersect($this->user->getUserDepartments(), $flowModel->getDepartments())))
		{
			return true;
		}

		return false;
	}

	/**
	 * @param AccessibleTask $oldTask
	 * @return bool
	 */

	private function canUserUpdateTaskAssigneeInFlow (AccessibleTask $oldTask, int $userId): bool
	{
		$flowId = $oldTask->getFlowId();

		if (
			$oldTask->getGroupId()
			&& $flowId
			&& Loader::includeModule("socialnetwork")
			&& $userId === FlowModel::createFromId($flowId)->getOwnerId()
		)
		{
			return true;
		}

		return false;
	}
}
