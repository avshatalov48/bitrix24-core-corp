<?php

namespace Bitrix\Crm\Agent\Security\Service\RoleSeparator;

use Bitrix\Crm\Agent\Security\Service\RoleSeparator;
use Bitrix\Crm\Category\PermissionEntityTypeHelper;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;

final class CustomSection extends RoleSeparator
{
	public function __construct(
		private readonly int $solutionId,
		private readonly array $typeIds,
	)
	{
	}

	protected function isPossibleToTransmit(EO_RolePermission $permission): bool
	{
		$entity = $permission->getEntity();
		$identifier = PermissionEntityTypeHelper::extractEntityEndCategoryFromPermissionEntityType($entity);
		if ($identifier === null)
		{
			return false;
		}

		return in_array($identifier->getEntityTypeId(), $this->typeIds, true);
	}

	protected function generateGroupCode(): string
	{
		return (string)\Bitrix\Crm\Security\Role\GroupCodeGenerator::getGroupCodeByAutomatedSolutionId($this->solutionId);
	}
}
