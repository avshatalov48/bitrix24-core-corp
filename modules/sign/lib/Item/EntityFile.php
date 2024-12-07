<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract;

class EntityFile implements Contract\Item
{
	public function __construct(
		public ?int $id,
		public int $entityTypeId,
		public int $entityId,
		public int $code,
		public int $fileId,
	)
	{}
}