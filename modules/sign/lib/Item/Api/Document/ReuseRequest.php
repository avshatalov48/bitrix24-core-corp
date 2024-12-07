<?php

namespace Bitrix\Sign\Item\Api\Document;

use Bitrix\Sign\Contract;

class ReuseRequest implements Contract\Item
{
	public function __construct(
		public string $documentUid,
		public string $sourceDocumentUid
	) {}
}
