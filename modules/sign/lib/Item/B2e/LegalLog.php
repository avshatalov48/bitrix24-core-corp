<?php

namespace Bitrix\Sign\Item\B2e;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract\Item;

class LegalLog implements Item
{
	public function __construct(
		public string $code,
		public int $documentId,
		public string $documentUid,
		public ?string $description = null,
		public ?int $memberId = null,
		public ?string $memberUid = null,
		public ?int $userId = null,
		public ?int $id = null,
		public ?DateTime $dateCreate = null,
	) {}
}
