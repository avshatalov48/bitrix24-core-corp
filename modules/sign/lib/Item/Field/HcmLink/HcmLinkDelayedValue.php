<?php

namespace Bitrix\Sign\Item\Field\HcmLink;

use Bitrix\Sign\Item\Api\Property\Request\Field\Fill\Value\StringFieldValue;

class HcmLinkDelayedValue extends StringFieldValue
{
	public function __construct(
		public readonly int $fieldId,
		public readonly int $employeeId,
	)
	{
		parent::__construct('');
	}
}