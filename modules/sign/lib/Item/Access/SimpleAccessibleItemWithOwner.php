<?php

namespace Bitrix\Sign\Item\Access;

use Bitrix\Sign\Contract;

final class SimpleAccessibleItemWithOwner implements Contract\Item, Contract\Access\AccessibleItemWithOwner, Contract\Item\ItemWithCrmId
{
	public function __construct(
		private int $id,
		private int $ownerId,
		private int $crmId = 0,
	)
	{
	}

	public static function createFromId(int $itemId): Contract\Access\AccessibleItemWithOwner
	{
		return new static($itemId, 0);
	}

	public function getOwnerId(): int
	{
		return $this->ownerId;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getCrmId(): int
	{
		return $this->crmId;
	}
}
