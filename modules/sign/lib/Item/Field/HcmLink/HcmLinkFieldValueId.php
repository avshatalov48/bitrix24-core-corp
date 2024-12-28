<?php

namespace Bitrix\Sign\Item\Field\HcmLink;

use Bitrix\Sign\Contract\Item;

class HcmLinkFieldValueId implements Item
{
	public function __construct(
		public readonly int $fieldId,
		public readonly int $employeeId,
	)
	{}
}