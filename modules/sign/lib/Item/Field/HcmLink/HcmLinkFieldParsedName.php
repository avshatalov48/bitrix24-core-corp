<?php

namespace Bitrix\Sign\Item\Field\HcmLink;

use Bitrix\Sign\Contract\Item;

class HcmLinkFieldParsedName implements Item
{
	public function __construct(
		public readonly int $integrationId,
		public readonly int $id,
		public readonly int $type,
		public readonly int $party,
	) {}
}