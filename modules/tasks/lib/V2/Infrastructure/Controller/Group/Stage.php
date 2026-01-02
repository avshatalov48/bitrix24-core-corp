<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Infrastructure\Controller\Group;

use Bitrix\Main\Engine\ActionFilter\Attribute\Rule\CloseSession;
use Bitrix\Tasks\V2\Infrastructure\Controller\BaseController;
use Bitrix\Tasks\V2\Internal\Entity;
use Bitrix\Tasks\V2\Internal\Access\Group\Permission;
use Bitrix\Tasks\V2\Internal\Repository\StageRepositoryInterface;

class Stage extends BaseController
{
	/**
	 * @ajaxAction tasks.V2.Group.Stage.list
	 */
	#[CloseSession]
	public function listAction(
		#[Permission\Read] Entity\Group $group,
		StageRepositoryInterface $stageRepository
	): ?Entity\StageCollection
	{
		return $stageRepository->getByGroupId($group->getId());
	}
}
