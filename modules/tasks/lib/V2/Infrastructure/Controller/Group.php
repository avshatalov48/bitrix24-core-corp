<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\V2\Internal\Access\Group\Permission;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Repository\GroupRepositoryInterface;

class Group extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Group.get
	 */
	#[CloseSession]
	public function getAction(
		#[Permission\Read]
		Entity\Group $group,
		GroupRepositoryInterface $groupRepository,
	): ?Entity\Group
	{
		return $groupRepository->getById($group->getId());
	}
}
