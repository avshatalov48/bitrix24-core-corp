<?php

namespace Bitrix\Crm\Service\Operation\Action;

use Bitrix\Crm\FieldContext\EntityFactory;
use Bitrix\Crm\FieldContext\Repository;
use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Operation\Action;
use Bitrix\Main\Result;

class DeleteEntityFieldsContext extends Action
{
	public function process(Item $item): Result
	{
		$result = new Result();

		$entityTypeId = $item->getEntityTypeId();
		$entity = EntityFactory::getInstance()->getEntity($entityTypeId);
		if ($entity)
		{
			$canDeleteByFieldName = (
				!\CCrmOwnerType::isUseDynamicTypeBasedApproach($entityTypeId)
				|| Repository::hasFieldsContextTables()
			);

			if ($canDeleteByFieldName)
			{
				$entity::deleteByItemId($this->getItemBeforeSave()->getId());
			}
		}

		return $result;
	}
}
