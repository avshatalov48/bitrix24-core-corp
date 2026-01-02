<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Entity\Trait;

use BackedEnum;
use Bitrix\Main\Type\DateTime;
use Bitrix\Tasks\V2\Internal\Entity\AbstractEntity;
use Bitrix\Tasks\V2\Internal\Entity\AbstractEntityCollection;
use Bitrix\Tasks\V2\Internal\Entity\ValueObjectInterface;

trait MapTypeTrait
{
	public static function mapInteger(array $props, string $key): ?int
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if (!is_numeric($value))
		{
			return null;
		}

		return (int)$value;
	}

	public static function mapString(array $props, string $key): ?string
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if (!is_string($value))
		{
			return null;
		}

		return $value;
	}

	/**
	 * @param class-string<BackedEnum> $enumClass
	 */
	public static function mapBackedEnum(array $props, string $key, string $enumClass): ?BackedEnum
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if ($value instanceof $enumClass)
		{
			/** @var BackedEnum $value */
			return $value;
		}

		$value = is_string($value) ? $value : static::mapInteger($props, $key);

		return $value !== null ? $enumClass::tryFrom($value) : null;
	}

	public static function mapBool(array $props, string $key): ?bool
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if (!is_bool($value))
		{
			return null;
		}

		return $value;
	}

	public static function mapArray(array $props, string $key, null|string|callable $typeCallback = null): ?array
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if (!is_array($value))
		{
			return null;
		}

		if (!is_callable($typeCallback))
		{
			return $value;
		}

		return array_map(static fn(mixed $item): mixed => $typeCallback($item), $value);
	}

	/**
	 * @param class-string<AbstractEntity> $entityClass
	 */
	public static function mapEntity(array $props, string $key, string $entityClass): ?AbstractEntity
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if ($value instanceof $entityClass)
		{
			/** @var AbstractEntity $value */
			return $value;
		}

		if (!is_array($value))
		{
			return null;
		}

		return $entityClass::mapFromArray($value);
	}

	/**
	 * @param class-string<ValueObjectInterface> $valueObjectClass
	 */
	public static function mapValueObject(array $props, string $key, string $valueObjectClass): ?ValueObjectInterface
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if ($value instanceof $valueObjectClass)
		{
			/** @var ValueObjectInterface $value */
			return $value;
		}

		if (!is_array($value))
		{
			return null;
		}

		return $valueObjectClass::mapFromArray($value);
	}

	/**
	 * @param class-string<AbstractEntityCollection> $entityCollectionClass
	 */
	public static function mapEntityCollection(array $props, string $key, string $entityCollectionClass): ?AbstractEntityCollection
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];
		if ($value instanceof $entityCollectionClass)
		{
			/** @var AbstractEntityCollection $value */
			return $value;
		}

		if (!is_array($value))
		{
			return null;
		}

		return $entityCollectionClass::mapFromArray($value);
	}

	public static function mapMixed(array $props, string $key): mixed
	{
		return $props[$key] ?? null;
	}

	public static function mapDateTime(array $props, string $key): ?DateTime
	{
		if (!isset($props[$key]))
		{
			return null;
		}

		$value = $props[$key];

		return $value instanceof DateTime ? $value : null;
	}
}
