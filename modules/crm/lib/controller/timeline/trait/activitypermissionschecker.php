<?php

namespace Bitrix\Crm\Controller\Timeline\trait;

use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator;
use Bitrix\Crm\ItemIdentifier;

trait ActivityPermissionsChecker
{
	protected function isUpdateEnable(int $ownerTypeId, int $ownerId): bool
	{
		$itemIdentifier = ItemIdentifier::createByParams($ownerTypeId, $ownerId);
		if ($itemIdentifier === null)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return false;
		}

		$result = (new Validator\Activity\UpdatePermission())->validate($itemIdentifier);

		if (!$result->isSuccess())
		{
			$this->addErrors($result->getErrors());
		}

		return $result->isSuccess();
	}
}
