<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Result;

class Opened extends Field
{
	public function processWithPermissions(Item $item, UserPermissions $userPermissions): Result
	{
		$operationType = ($item->isNew() ? UserPermissions::OPERATION_ADD : UserPermissions::OPERATION_UPDATE);

		$permissionType = $userPermissions->getPermissionType(
			$item,
			$operationType
		);

		if($permissionType === UserPermissions::PERMISSION_OPENED)
		{
			$item->set($this->getName(), true);
		}

		return parent::processWithPermissions($item, $userPermissions);
	}
}