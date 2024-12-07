<?php

namespace Bitrix\HumanResources\Trait;

use ReflectionClass;

trait ConvertToArray
{
	public function toArray(): array
	{
		$reflection = new ReflectionClass($this);
		$properties = $reflection->getProperties();

		$result = [];
		foreach ($properties as $property)
		{
			$name = $property->getName();

			if (isset($this->{$name}))
			{
				$result[$name] = $this->{$name};
			}
		}

		return $result;
	}
}