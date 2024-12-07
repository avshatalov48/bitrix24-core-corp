<?php

namespace Bitrix\Sign\Item\Api\Document\Page;

use Bitrix\Sign\Contract;

class ListRequest implements Contract\Item
{
	public string $documentUid;

	public function __construct(string $documentUid)
	{
		$this->documentUid = $documentUid;
	}
}