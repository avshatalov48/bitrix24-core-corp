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
			$myCompanyPermissions = Container::getInstance()->getUserPermissions()->getMyCompanyPermissions();

			return $myCompanyPermissions->canUpdate();
		}

		if ($entityId)
		{
			return $this->userPermissions->checkUpdatePermissions($entityTypeId, $entityId, $categoryId);
		}

		return $this->userPermissions->checkAddPermissions($entityTypeId, $categoryId);
	}
}
