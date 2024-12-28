<?php

namespace Bitrix\Sign\Factory\Access;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;

class AccessibleItemFactory
{
	public function createFromItem(Contract\Item $item): ?Contract\Access\AccessibleItem
	{
		if ($item instanceof Item\Document)
		{
			return new Item\Access\Document($item);
		}
		if (!$item instanceof Contract\Item\ItemWithOwner)
		{
			return null;
		}

		$crmId = $item instanceof Contract\Item\ItemWithCrmId
			? $item->getCrmId()
			: 0
		;

		return new Item\Access\SimpleAccessibleItemWithOwner($item->getId(), $item->getOwnerId(), $crmId);
	}
}