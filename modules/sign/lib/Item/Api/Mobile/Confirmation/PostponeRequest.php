<?php

namespace Bitrix\Sign\Item\Api\Mobile\Confirmation;

use Bitrix\Sign\Contract;

class PostponeRequest implements Contract\Item
{
	public string $documentUid;
	public string $memberUid;

	public function __construct(string $documentUid, string $memberUid)
	{
		$this->documentUid = $documentUid;
		$this->memberUid = $memberUid;
	}
}
