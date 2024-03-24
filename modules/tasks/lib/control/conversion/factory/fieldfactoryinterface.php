<?php

namespace Bitrix\Tasks\Control\Conversion\Factory;

use Bitrix\Tasks\Control\Conversion\Field;

interface FieldFactoryInterface
{
	public static function createByData(string $key, mixed $value): Field;
}