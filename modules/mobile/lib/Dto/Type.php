<?php

namespace Bitrix\Mobile\Dto;

use Bitrix\Mobile\Dto\Caster\Caster;
use Bitrix\Mobile\Dto\Caster\ScalarCaster;
use Bitrix\Mobile\Dto\Caster\ObjectCaster;

final class Type
{
	public static function makeCasterByPropertyType(\ReflectionType $type): ?Caster
	{
		if (!method_exists($type, 'getName'))
		{
			return null;
		}

		$typeName = $type->getName();

		$caster = self::resolveCasterByTypeName($typeName);

		if ($caster && $type->allowsNull())
		{
			$caster->nullable();
		}

		return $caster;
	}

	public static function makeCollectionCasterFromAttributes(\ReflectionAttribute $attribute, $isNullable = false): ?Caster
	{
		$elementsType = $attribute->getArguments()[0] ?? null;
		if (!$elementsType)
		{
			throw new InvalidDtoException('Type of collection is not declared in attribute');
		}

		$caster = self::resolveCasterByTypeName($elementsType);

		return Type::collection($caster)->nullable($isNullable);
	}

	public static function validateCollectionByAttributes(?array $collection, \ReflectionAttribute $attribute): void
	{
		$elementsType = $attribute->newInstance()->getElementsType();

		foreach ($collection as $item)
		{
			$type = gettype($item);
			if ($type !== $elementsType)
			{
				throw new InvalidDtoException('Invalid type of element in collection: expected ' . $elementsType . ', got ' . $type);
			}
		}
	}

	private static function resolveCasterByTypeName($typeName)
	{
		$scalarTypes = ['int', 'float', 'string', 'bool'];

		if (in_array($typeName, $scalarTypes))
		{
			return new ScalarCaster($typeName);
		}

		if (class_exists($typeName))
		{
			return new ObjectCaster($typeName);
		}

		return null;
	}

	public static function int(): ScalarCaster
	{
		return new ScalarCaster('int');
	}

	public static function float(): ScalarCaster
	{
		return new ScalarCaster('float');
	}

	public static function string(): ScalarCaster
	{
		return new ScalarCaster('string');
	}

	public static function bool(): ScalarCaster
	{
		return new ScalarCaster('bool');
	}

	public static function object(string $type): ObjectCaster
	{
		return new ObjectCaster($type);
	}

	/**
	 * @param string|Caster $type
	 * @return Caster
	 */
	public static function collection($type): Caster
	{
		if ($type instanceof Caster)
		{
			$type->markAsCollection();
			return $type;
		}
		else
		{
			return new ObjectCaster($type, true);
		}
	}
}