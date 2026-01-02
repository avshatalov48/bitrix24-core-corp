<?php

namespace Bitrix\HumanResources\Item\Collection;

use Bitrix\HumanResources\Item;

/**
 * @extends BaseCollection<Item\Role>
 */
class RoleCollection extends BaseCollection
{
	public function getItemByXmlId(string $xmlId): ?Item\Role
	{
		foreach ($this->itemMap as $item)
		{
			if ($item->xmlId === $xmlId)
			{
				return $item;
			}
		}

		return null;
	}
}