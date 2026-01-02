<?php

namespace Bitrix\Sign\Item\Member;

use Bitrix\Sign\Contract\Item;

class SelectorEntity implements Item
{
	public function __construct(
		public readonly string $entityType,
		public readonly string $entityId,
		public readonly ?string $role = null,
		public readonly int $party = 0,
	) {}
}