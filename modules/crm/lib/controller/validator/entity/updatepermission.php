<?php

namespace Bitrix\Crm\Controller\Validator\Entity;

use Bitrix\Crm\ItemIdentifier;

class UpdatePermission extends AbstractPermission
{
	protected function checkPermissions(int $entityTypeId, int $entityId, ?int $categoryId = null): bool
	{
		return $this->userPermissions->item()->canUpdateItemIdentifier(new ItemIdentifier($entityTypeId, $entityId, $categoryId));
	}
}
