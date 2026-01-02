<?php

namespace Bitrix\Crm\Agent\Security\Service\RoleSeparator;

use Bitrix\Crm\Agent\Security\Service\RoleSeparator;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;

final class PermissionTypeList extends RoleSeparator
{
	public function __construct(
		private readonly array $permissionEntities,
		private readonly string $groupCode,
	)
	{
	}

	protected function isPossibleToTransmit(EO_RolePermission $permission): bool
	{
		return in_array($permission->getEntity(), $this->permissionEntities, true);
	}

	protected function generateGroupCode(): string
	{
		return $this->groupCode;
	}
}
