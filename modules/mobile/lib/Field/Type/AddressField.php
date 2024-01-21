<?php

namespace Bitrix\Mobile\Field\Type;

use Bitrix\Fileman\UserField\Types\AddressType;
use Bitrix\Main\Loader;

class AddressField extends BaseField
{
	public const TYPE = 'address';

	/**
	 * @inheritDoc
	 */
	public function getFormattedValue()
	{
		Loader::includeModule('fileman');

		if ($this->isMultiple())
		{
			if (!$this->value)
			{
				return [];
			}

			$result = [];

			foreach ($this->value as $value)
			{
				$result[] = AddressType::parseValue($value);
			}

			return $result;
		}

		if (empty($this->value))
		{
			return null;
		}

		return AddressType::parseValue($this->value);
	}
}
