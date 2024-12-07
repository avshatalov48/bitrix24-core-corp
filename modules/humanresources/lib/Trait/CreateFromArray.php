<?php

namespace Bitrix\HumanResources\Trait;

use ReflectionClass;
use TypeError;
use Bitrix\Main\Type\Contract\Arrayable;

trait CreateFromArray
{
	public static function createFromArray(array|Arrayable $data): static
	{
		$reflection = new ReflectionClass(static::class);

		if ($data instanceof Arrayable)
		{
			$data = $data->toArray();
		}

		/* @todo Implement constructor variation of creation */
		$instance = new static();
		foreach ($data as $key => $value)
		{
			if (!$reflection->hasProperty($key))
			{
				continue;
			}

			try
			{
				$instance->{$key} = $value;
			}
			catch (TypeError)
			{
			}
		}

		return $instance;
	}
}