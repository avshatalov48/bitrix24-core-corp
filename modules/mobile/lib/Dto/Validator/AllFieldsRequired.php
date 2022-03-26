<?php

namespace Bitrix\Mobile\Dto\Validator;

use Bitrix\Mobile\Dto\Property;

class AllFieldsRequired extends Validator
{
	/**
	 * @param array $fields
	 * @param Property[] $properties
	 * @return bool
	 */
	public function __invoke(array $fields, array $properties): bool
	{
		foreach ($properties as $property)
		{
			$name = $property->getName();
			if (!array_key_exists($name, $fields))
			{
				return false;
			}
		}
		return true;
	}
}