<?php

namespace Bitrix\Sign\Item\Field\HcmLink;

use Bitrix\Sign\Contract\Item;

class HcmLinkFieldValueId implements Item
{
	public function __construct(
		public readonly string $entityType,
		public readonly int $fieldId,
		public readonly int $employeeId,
		public readonly ?int $signerId = null,
	)
	{}
}