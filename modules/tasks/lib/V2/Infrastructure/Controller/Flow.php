<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\V2\Internal\Access\Flow\Permission;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\FlowRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface;

class Flow extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Flow.get
	 */
	#[CloseSession]
	public function getAction(
		#[Permission\Read] Entity\Flow $flow,
		FlowRepositoryInterface $flowRepository,
		GroupRepositoryInterface $groupRepository,
	): ?Entity\Flow
	{
		$flow = $flowRepository->getById($flow->getId());
		if ($flow === null || $flow->group === null)
		{
			return null;
		}

		$group = $groupRepository->getById($flow->group->getId());
		if ($group === null)
		{
			return $flow;
		}

		return $flow->cloneWith(['group' => $group->toArray()]);
	}
}
