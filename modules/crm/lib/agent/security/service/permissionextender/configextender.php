<?php

namespace Bitrix\Crm\Agent\Security\Service\PermissionExtender;

use Bitrix\Crm\Agent\Security\Service\PermissionExtender;
use Bitrix\Crm\Agent\Security\Service\PermissionTransmitter\Copy;
use Bitrix\Crm\Agent\Security\Service\Support\PermissionsUtil;
use Bitrix\Crm\Security\Role\Manage\DTO\PermissionModel;
use Bitrix\Crm\Security\Role\Model\EO_Role;
use Bitrix\Crm\Security\Role\Model\EO_RolePermission;
use Bitrix\Crm\Security\Role\Utils\RolePermissionChecker;

final class ConfigExtender implements PermissionExtender
{
	public function __construct(
		private readonly string $newEntity,
	)
	{
	}

	public function expand(EO_Role $targetRole, EO_Role $sourceRole): bool
	{
		$configPermission = PermissionsUtil::findNotEmptyCrmConfig($sourceRole);
		if ($configPermission === null)
		{
			return false;
		}

		$transmitter = new Copy($targetRole, ['ENTITY' => $this->newEntity]);
		$transmitter->transmit($configPermission);

		return true;
	}
}
