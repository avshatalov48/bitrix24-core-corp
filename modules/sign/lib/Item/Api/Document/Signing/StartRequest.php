<?php

namespace Bitrix\Sign\Item\Api\Document\Signing;

use Bitrix\Sign\Contract;

class StartRequest implements Contract\Item
{
	public string $documentUid;

	public function __construct(string $documentUid)
	{
		$this->documentUid = $documentUid;
	}
}