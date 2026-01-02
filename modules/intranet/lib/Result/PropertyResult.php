<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Result;

use Bitrix\Main;

abstract class PropertyResult extends Main\Result
{
	public function getData(): array
	{
		$result = [];

		$class = new \ReflectionClass(static::class);
		$properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
		foreach ($properties as $property)
		{
			$result[$property->getName()] = $property->getValue($this);
		}

		return $result;
	}
}
