<?php

namespace Bitrix\Crm\Controller\Validator\Entity;

use Bitrix\Crm\ItemIdentifier;

class ReadPermission extends AbstractPermission
{
	protected function checkPermissions(int $entityTypeId, int $entityId, ?int $categoryId = null): bool
	{
		return $this->userPermissions->item()->canReadItemIdentifier(new ItemIdentifier($entityTypeId, $entityId, $categoryId));
	}
}
