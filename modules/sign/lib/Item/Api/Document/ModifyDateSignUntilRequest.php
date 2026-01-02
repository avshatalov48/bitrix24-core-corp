<?php

namespace Bitrix\Sign\Item\Api\Document;

use Bitrix\Sign\Contract;

class ModifyDateSignUntilRequest implements Contract\Item
{
	public function __construct(
		public string $documentUid,
		public int $timestamp,
	) {}
}
