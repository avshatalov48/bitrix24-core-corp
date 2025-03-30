<?php

namespace Bitrix\Sign\Item\Api\Mobile\Signing;

use Bitrix\Sign\Contract;

class ReviewRequest implements Contract\Item
{
	public function __construct(
		readonly string $documentUid,
		readonly string $memberUid,
	) {}
}

