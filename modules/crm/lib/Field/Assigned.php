<?php

namespace Bitrix\Crm\Field;

use Bitrix\Crm\EventRelationsTable;
use Bitrix\Crm\Field;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Operation\FieldAfterSaveResult;
use Bitrix\Crm\Service\UserPermissions;
use Bitrix\Main\Result;

class Assigned extends Field
{
	public function processWithPermissions(Item $item, UserPermissions $userPermissions): Result
	{
		if($item->isNew())
		{
			$permissionType = $userPermissions->getPermissionType(
				$item,
				UserPermissions::OPERATION_ADD
			);

			if($permissionType === UserPermissions::PERMISSION_SELF)
			{
				$item->set($this->getName(), $userPermissions->getUserId());
			}
		}

		return parent::processWithPermissions($item, $userPermissions);
	}

	protected function processLogic(Item $item, Context $context = null): Result
	{
		if (!$context)
		{
			$context = Container::getInstance()->getContext();
		}

		if($this->isItemValueEmpty($item))
		{
			$item->set($this->getName(), $context->getUserId());
		}

		return parent::processLogic($item, $context);
	}

	public function processAfterSave(Item $itemBeforeSave, Item $item, Context $context = null): FieldAfterSaveResult
	{
		$result = new FieldAfterSaveResult();

		if ($itemBeforeSave->remindActual(Item::FIELD_NAME_ASSIGNED) !== $item->getAssignedById())
		{
			$updateResult = EventRelationsTable::setAssignedByItem(
				ItemIdentifier::createByItem($item),
				(int)$item->getAssignedById(),
			);
			if (!$updateResult->isSuccess())
			{
				$result->addErrors($updateResult->getErrors());
			}
		}

		return $result;

	}
}
