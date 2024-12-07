<?php

namespace Bitrix\Sign\Item\Api\Mobile\Signing;

use Bitrix\Sign\Contract;

class ExternalUrlRequest implements Contract\Item
{
	public function __construct(
		public string $documentUid,
		public string $memberUid,
	) {}
}