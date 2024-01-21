<?php

namespace Bitrix\Mobile\Field\Type;

class BooleanField extends BaseField
{
	public const TYPE = 'boolean';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		return ($this->value === true || $this->value === 'Y' || $this->value === 1 || $this->value === '1');
	}
}
