<?php

namespace Bitrix\Tasks\Internals\Trait;

use ReflectionClass;

trait ToArrayTrait
{
	public function toArray(bool $withDefault = true): array
	{
		$reflection = new ReflectionClass($this);
		$properties = $reflection->getProperties();
		$map = [];
		foreach ($properties as $property)
		{
			$name = $property->getName();
			$key = static::modifyKeyToArray($name);

			if (
				isset($this->{$name})
				&& $property->hasDefaultValue()
				&& $property->getDefaultValue() === $this->{$name}
			)
			{
				if ($withDefault === true)
				{
					$map[$key] = $property->getDefaultValue();
				}
				else
				{
					continue;
				}
			}

			if (isset($this->{$name}))
			{
				$map[$key] = $this->{$name};
			}
		}

		return $map;
	}

	protected static function modifyKeyToArray(string $key): string
	{
		return $key;
	}
}