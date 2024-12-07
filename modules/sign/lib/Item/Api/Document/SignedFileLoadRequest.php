<?php

namespace Bitrix\Sign\Item\Api\Document;

use Bitrix\Sign\Contract;
class SignedFileLoadRequest implements Contract\Item
{
	public function __construct(public string $documentId, public ?string $memberId = null)
	{}
}