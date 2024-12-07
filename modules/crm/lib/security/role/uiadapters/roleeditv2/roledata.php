<?php

namespace Bitrix\Crm\Security\Role\UIAdapters\RoleEditV2;

use Bitrix\Crm\Security\Role\Manage\DTO\EntityDTO;
use Bitrix\Crm\Security\Role\Manage\DTO\Restrictions;

class RoleData
{
	public function __construct(
		private RoleDTO $role,
		/** @var EntityDTO[] */
		private array $entities,
		private array $userAssigned,
		private Restrictions $restriction
	)
	{
	}

	public function role(): RoleDTO
	{
		return $this->role;
	}

	public function entities(): array
	{
		return $this->entities;
	}

	public function userAssigned(): array
	{
		return $this->userAssigned;
	}

	public function restriction(): Restrictions
	{
		return $this->restriction;
	}


}