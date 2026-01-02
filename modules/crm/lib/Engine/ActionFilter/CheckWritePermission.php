<?php

declare(strict_types = 1);

namespace Bitrix\Crm\Engine\ActionFilter;

use Bitrix\Crm\Service\Container;

class CheckWritePermission extends BaseCheckPermission
{
	protected function checkItemPermission(int $entityTypeId, int $entityId = 0, ?int $categoryId = null): bool
	{
		if ($entityTypeId === \CCrmOwnerType::Company && \CCrmCompany::isMyCompany($entityId))
		{
			$myCompanyPermissions = Container::getInstance()->getUserPermissions()->myCompany();

			return $myCompanyPermissions->canUpdate();
		}

		if ($entityId)
		{
			return $this->userPermissions->item()->canUpdate($entityTypeId, $entityId);
		}

		return
			is_null($categoryId)
			? $this->userPermissions->entityType()->canAddItems($entityTypeId)
			: $this->userPermissions->entityType()->canAddItemsInCategory($entityTypeId, $categoryId)
		;
	}
}
