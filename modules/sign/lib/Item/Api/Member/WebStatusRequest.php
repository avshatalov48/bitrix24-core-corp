<?php

namespace Bitrix\Sign\Item\Api\Member;

use Bitrix\Sign\Contract\Item;

class WebStatusRequest implements Item
{
	public function __construct(
		public readonly string $memberUid,
		public readonly string $documentUid
	) {}
}