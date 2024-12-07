<?php

namespace Bitrix\Crm\Controller\Validator\Entity;

class ReadPermission extends AbstractPermission
{
	protected function checkPermissions(int $entityTypeId, int $entityId, ?int $categoryId = null): bool
	{
		return $this->userPermissions->checkReadPermissions($entityTypeId, $entityId, $categoryId);
	}
}
