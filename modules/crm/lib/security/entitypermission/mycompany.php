<?php

namespace Bitrix\Crm\Security\EntityPermission;

use Bitrix\Crm\Service\UserPermissions;

class MyCompany
{
	private UserPermissions $userPermissions;

	public function __construct(UserPermissions $userPermissions)
	{
		$this->userPermissions = $userPermissions;
	}
	public function canSearch(): bool
	{
		return $this->canReadBaseFields();
	}

	public function canReadBaseFields(?int $myCompanyId = null): bool
	{
		$baseEntityTypeIds = [
			\CCrmOwnerType::Company,
			\CCrmOwnerType::SmartInvoice,
			\CCrmOwnerType::SmartDocument,
		];

		foreach ($baseEntityTypeIds as $baseEntityTypeId)
		{
			if ($this->userPermissions->canReadType($baseEntityTypeId))
			{
				return true;
			}
		}

		return false;
	}

	public function canRead(): bool
	{
		return $this->userPermissions->canWriteConfig();
	}

	public function canAdd(): bool
	{
		return $this->userPermissions->canWriteConfig();
	}

	public function canUpdate(): bool
	{
		return $this->userPermissions->canWriteConfig();
	}

	public function canDelete(): bool
	{
		return $this->userPermissions->canWriteConfig();
	}

	public function canAddByOwnerEntity(int $ownerEntityTypeId, ?int $ownerEntityId = null): bool
	{
		if ($ownerEntityTypeId === \CCrmOwnerType::SmartDocument)
		{
			return $this->userPermissions->checkAddPermissions(\CCrmOwnerType::SmartDocument);
		}

		return $this->canAdd();
	}

	public function canUpdateByOwnerEntity(int $ownerEntityTypeId, ?int $ownerEntityId = null): bool
	{
		if ($ownerEntityTypeId === \CCrmOwnerType::SmartDocument)
		{
			return $this->userPermissions->checkUpdatePermissions($ownerEntityTypeId, $ownerEntityId);
		}

		return $this->canUpdate();
	}
}
