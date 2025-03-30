<?php

namespace Bitrix\Sign\Helper;

use Bitrix\Sign\Attribute\Copyable;

final class CloneHelper
{
	/**
	 * @template ObjT of object
	 * @template T of ObjT|null
	 * @psalm-param T $value
	 *
	 * @return T
	 * @psalm-return (T is null ? null : ObjT)
	 */
	public static function cloneIfNotNull(?object $value): ?object
	{
		return $value !== null ? clone $value : null;
	}

	public static function copyPropertiesIfPossible(object $original, object $copy): void
	{
		$reflection = new \ReflectionClass($original);
		$properties = $reflection->getProperties();
		foreach ($properties as $property)
		{
			if ($property->getAttributes(Copyable::class))
			{
				$value = $property->getValue($original);
				$property->setValue($copy, $value);
			}
		}
	}
}