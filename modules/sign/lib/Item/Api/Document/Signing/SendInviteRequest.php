<?php

namespace Bitrix\Sign\Item\Api\Document\Signing;

use Bitrix\Sign\Contract;

class SendInviteRequest implements Contract\Item
{
	public function __construct(
		public string $documentUid,
		public string $memberUid,
	) {}
}