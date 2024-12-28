<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Security\Role\Model\EO_RolePermission;

interface PermissionTransmitter
{
	public function transmit(EO_RolePermission $permission): bool;
}
