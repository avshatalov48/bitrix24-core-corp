<?php

namespace Bitrix\HumanResources\Item\Collection\HcmLink;

use Bitrix\HumanResources\Item\Collection\BaseCollection;
use Bitrix\HumanResources\Item;

/**
 * @extends BaseCollection<Item\HcmLink\MemberMap>
 */
class MemberMapCollection extends BaseCollection
{
	public function getExternalIds(): array
	{
		return array_values(array_map(fn(Item\HcmLink\MemberMap $item) => $item->externalId, $this->itemMap));
	}

	public function getEntityIds(): array
	{
		return array_values(array_map(fn(Item\HcmLink\MemberMap $item) => $item->entityId, $this->itemMap));
	}
}