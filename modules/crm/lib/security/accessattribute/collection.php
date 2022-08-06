<?php

namespace Bitrix\Crm\Security\AccessAttribute;

class Collection
{
	protected $attributesByEntityType = [];
	protected $userId = 0;

	public function __construct(int $userId)
	{
		$this->userId = $userId;
	}

	public function getUserId(): int
	{
		return $this->userId;
	}

	public function addByEntityType(string $permissionEntityType, array $attributes): void
	{
		if (!isset($this->attributesByEntityType[$permissionEntityType]))
		{
			$this->attributesByEntityType[$permissionEntityType] = [];
		}
		$this->attributesByEntityType[$permissionEntityType] = array_merge(
			$this->attributesByEntityType[$permissionEntityType],
			$attributes
		);
	}

	public function getByEntityType($permissionEntityType): array
	{
		return $this->attributesByEntityType[$permissionEntityType] ?? [];
	}

	public function getAllowedEntityTypes(): array
	{
		$allowedPermissionEntityTypes = [];

		foreach ($this->attributesByEntityType as $permissionEntityType => $entityUserAttributes)
		{
			if (!empty($entityUserAttributes))
			{
				$allowedPermissionEntityTypes[] = $permissionEntityType;
			}
		}

		return $allowedPermissionEntityTypes;
	}

	public function areAllEntityTypesAllowed(): bool
	{
		foreach ($this->attributesByEntityType as $entityUserAttributes)
		{
			if (empty($entityUserAttributes))
			{
				return false;
			}
		}

		return true;
	}
}
