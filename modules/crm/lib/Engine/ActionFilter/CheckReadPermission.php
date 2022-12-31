<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Engine\ActionFilter;

class CheckReadPermission extends BaseCheckPermission
{
	protected function checkItemPermission(int $entityTypeId, int $entityId = 0, ?int $categoryId = null): bool
	{
		return $this->userPermissions->checkReadPermissions($entityTypeId, $entityId, $categoryId);
	}
}