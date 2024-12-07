<?php

namespace Bitrix\Tasks\Internals\Trait;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Internals\Attribute\trait\PrimaryTrait;
use InvalidArgumentException;
use ReflectionClass;
use TypeError;

trait FromArrayTrait
{
	use PrimaryTrait;

	/**
	 * @throws InvalidArgumentException
	 */
	public static function createFromArray(array|Arrayable $data): static
	{
		$reflection = new ReflectionClass(static::class);
		if (!$reflection->isInstantiable())
		{
			throw new InvalidArgumentException(static::class . ' is not instantiable');
		}

		if ($data instanceof Arrayable)
		{
			$data = $data->toArray();
		}

		$instance = new static();

		foreach ($data as $key => $value)
		{
			$key = static::modifyKeyFromArray($key);
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

	protected static function modifyKeyFromArray(string $key): string
	{
		return $key;
	}
}