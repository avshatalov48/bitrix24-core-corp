<?php

namespace Bitrix\Crm\Controller\Validator\Entity;

class UpdatePermission extends AbstractPermission
{
	protected function checkPermissions(int $entityTypeId, int $entityId, ?int $categoryId = null): bool
	{
		return $this->userPermissions->checkUpdatePermissions($entityTypeId, $entityId, $categoryId);
	}
}
