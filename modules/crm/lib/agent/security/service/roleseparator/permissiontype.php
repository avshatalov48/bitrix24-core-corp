<?php

namespace Bitrix\Crm\Agent\Security\Service\RoleSeparator;

use Bitrix\Crm\Agent\Security\Service\RoleSeparator;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;

final class PermissionType extends RoleSeparator
{
	public function __construct(
		private readonly string $permissionEntity,
		private readonly string $groupCode,
	)
	{
	}

	protected function isPossibleToTransmit(EO_RolePermission $permission): bool
	{
		return $permission->getEntity() === $this->permissionEntity;
	}

	protected function generateGroupCode(): string
	{
		return $this->groupCode;
	}
}
