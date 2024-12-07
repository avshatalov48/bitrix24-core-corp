<?php

namespace Bitrix\Tasks\Access\Rule\Traits;

use Bitrix\Tasks\Flow\Access\FlowModel;
use Bitrix\Tasks\Flow\FlowRegistry;

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
}