<?php

namespace Bitrix\Crm\Security\Role\Manage\Entity;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;

interface PermissionEntity
{
	/**
	 * @return EntityDTO[]
	 */
	public function make(): array;
}