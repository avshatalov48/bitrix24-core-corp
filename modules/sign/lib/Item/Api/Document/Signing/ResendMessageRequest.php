<?php

namespace Bitrix\Sign\Item\Api\Document\Signing;

use Bitrix\Sign\Contract;

class ResendMessageRequest implements Contract\Item
{
	public function __construct(
		public string $documentUid,
		public string $memberUid,
	) {}
}
