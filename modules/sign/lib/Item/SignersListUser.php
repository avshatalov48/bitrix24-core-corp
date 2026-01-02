<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Type;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Attribute\Copyable;
use Bitrix\Sign\Item\TrackableItemTrait;

class SignersListUser implements Contract\Item
{
	public function __construct(
		public int $listId,
		public int $userId,
		public int $createdById,
		public Type\DateTime $dateCreate = new Type\DateTime(),
	)
	{
	}
}
