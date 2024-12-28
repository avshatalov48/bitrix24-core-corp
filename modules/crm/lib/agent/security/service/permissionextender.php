<?php

namespace Bitrix\Crm\Agent\Security\Service;

use Bitrix\Crm\Security\Role\Model\EO_Role;

interface PermissionExtender
{
	public function expand(EO_Role $targetRole, EO_Role $sourceRole): bool;
}
