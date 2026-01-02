<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Type;

final class Binding implements Item
{
	public function __construct(
		public int $entityId,
		public int $entityType,
	)
	{
	}
}