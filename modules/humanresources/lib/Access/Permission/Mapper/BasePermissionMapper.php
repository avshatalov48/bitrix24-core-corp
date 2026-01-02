<?php

namespace Bitrix\HumanResources\Access\Permission\Mapper;

abstract class BasePermissionMapper
{
	private ?string $permissionId = null;

	public function getPermissionId(): ?string
	{
		return $this->permissionId;
	}

	public function setPermissionId(string $permissionId): static
	{
		$this->permissionId = $permissionId;

		return $this;
	}
}