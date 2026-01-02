<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Migration\Exclusion;

use Bitrix\Tasks\Flow\Migration\Exclusion\Service\AbstractExclusionService;
use Bitrix\Tasks\Flow\Internal\Entity\Role;
use Bitrix\Tasks\Flow\Migration\Exclusion\Service\Responsible\BaseResponsibleExclusionService;
use Bitrix\Tasks\Flow\Migration\Exclusion\Service\Responsible\ManuallyResponsibleExclusionService;
use Bitrix\Tasks\Flow\Migration\Exclusion\Service\TaskCreator\TaskCreatorExclusionService;

class ExclusionServiceFactory
{
	public static function getByExcludedRole(Role $role): ?AbstractExclusionService
	{
		return match ($role)
		{
			Role::QUEUE_ASSIGNEE,
			Role::HIMSELF_ASSIGNED => new BaseResponsibleExclusionService(),
			Role::MANUAL_DISTRIBUTOR => new ManuallyResponsibleExclusionService(),
			Role::TASK_CREATOR => new TaskCreatorExclusionService(),
			default => null,
		};
	}
}
