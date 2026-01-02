<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Trait;

trait FindTrait
{
	abstract public function getIterator(): \Traversable;

	public function __call(string $name, array $args = [])
	{

	}

	public function findOne(array $conditions): ?static
	{
		$result = [];
		foreach ($this as $item)
		{
			foreach ($conditions as $condition)
			{
				[$key, $value] = $condition;
				if (!property_exists($item, $key))
				{
					break;
				}
				if ($this->{$key} !== $value)
				{
					break;
				}
			}

			$result[] = $item;
		}

		return new static(...$result);
	}
}