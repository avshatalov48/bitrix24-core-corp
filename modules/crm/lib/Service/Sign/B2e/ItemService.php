<?php

namespace Bitrix\Crm\Service\Sign\B2e;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;

/**
 * Service for working with b2e items.
 */
final class ItemService
{
	public function updateStageIdByStageIds(string $newStageId, array $whereStageIds): void
	{
		$factory = Container::getInstance()->getFactory(\CCrmOwnerType::SmartB2eDocument);
		$items = $factory->getItems([
			'filter' => [
				'@' . Item::FIELD_NAME_STAGE_ID => $whereStageIds,
			]
		]);

		foreach ($items as $item)
		{
			$item->setStageId($newStageId);
			$factory->getUpdateOperation($item)->launch();
		}
	}
}
