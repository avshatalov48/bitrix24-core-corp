<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Engine\ActionFilter;

class CheckReadPermission extends BaseCheckPermission
{
	protected function checkItemPermission(int $entityTypeId, int $entityId = 0, ?int $categoryId = null): bool
	{
		if ($entityId > 0)
		{
			return $this->userPermissions->item()->canRead($entityTypeId, $entityId);
		}
		if (!is_null($categoryId))
		{
			return $this->userPermissions->entityType()->canReadItemsInCategory($entityTypeId, $categoryId);
		}

		return $this->userPermissions->entityType()->canReadItems($entityTypeId);
	}
}