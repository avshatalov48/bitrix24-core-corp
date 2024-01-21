<?php

namespace Bitrix\Mobile\Field\Type;

class StringField extends BaseField
{
	public const TYPE = 'string';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		if (is_null($this->value) || $this->value === '')
		{
			return $this->isMultiple() ? [] : '';
		}

		return $this->value;
	}
}
