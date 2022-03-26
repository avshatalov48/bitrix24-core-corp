<?php

namespace Bitrix\Mobile\Dto\Validator;

use Bitrix\Mobile\Dto\Property;

abstract class Validator
{
	/**
	 * @param array $fields
	 * @param Property[] $properties
	 * @return bool
	 */
	public abstract function __invoke(array $fields, array $properties): bool;
}