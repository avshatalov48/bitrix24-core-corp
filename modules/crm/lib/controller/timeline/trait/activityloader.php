<?php

namespace Bitrix\Crm\Controller\Timeline\trait;

use Bitrix\Crm\Activity\BindIdentifier;
use Bitrix\Crm\Controller\ErrorCode;
use Bitrix\Crm\Controller\Validator;
use Bitrix\Crm\Controller\Validator\Validation;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;

trait ActivityLoader
{
	protected function loadActivity(int $activityId, int $ownerTypeId, int $ownerId): ?array
	{
		$itemIdentifier = ItemIdentifier::createByParams($ownerTypeId, $ownerId);
		if ($itemIdentifier === null)
		{
			$this->addError(ErrorCode::getOwnerNotFoundError());

			return null;
		}

		$binding = new BindIdentifier($itemIdentifier, $activityId);

		$validation = (new Validation())
			->validate($binding->getActivityId(), [new Validator\Activity\ActivityExists()])
			->validate($binding, [new Validator\Activity\BindingExists()])
			->validate($itemIdentifier, [new Validator\Activity\ReadPermission()])
		;

		if (!$validation->isSuccess())
		{
			$this->addErrors($validation->getErrors());

			return null;
		}

		return Container::getInstance()->getActivityBroker()->getById($activityId);
	}
}
