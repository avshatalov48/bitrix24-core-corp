<?php

namespace Bitrix\Crm\Agent\Security\Service\PermissionTransmitter;

use Bitrix\Crm\Agent\Security\Service\PermissionTransmitter;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;

final class Move implements PermissionTransmitter
{
	public function __construct(
		private readonly EO_Role $fromRole,
		private readonly EO_Role $targetRole,
	)
	{
	}

	public function transmit(EO_RolePermission $permission): bool
	{
		if (!$this->fromRole->getPermissions()?->has($permission))
		{
			return false;
		}

		$this->fromRole->removeFromPermissions($permission);
		$this->targetRole->addToPermissions($permission);

		$permission->setRole($this->targetRole);

		return true;
	}
}
