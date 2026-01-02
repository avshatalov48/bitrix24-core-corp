<?php

namespace Bitrix\Sign\Type\Trait;

use Bitrix\Sign\Attribute\Type\AsInt;
use Bitrix\Sign\Exception\LogicException;
use Bitrix\Sign\Exception\SignException;
use Bitrix\Sign\Exception\UnexpectedArgumentException;

trait IntEnumTrait
{
	public function toInt(): int
	{
		if (!$this instanceof \UnitEnum)
		{
			throw new LogicException('Trait IntEnumTrait can only be applied to enum elements.');
		}

		$enumCase = new \ReflectionEnumUnitCase($this, $this->name);

		return self::resolveAsIntValue($enumCase);
	}

	public static function fromInt(int $value): static
	{
		$reflectionEnum = new \ReflectionEnum(static::class);
		foreach ($reflectionEnum->getCases() as $case)
		{
			try
			{
				$caseValue = self::resolveAsIntValue($case);
			}
			catch (\Throwable $e)
			{
				continue;
			}
			if ($caseValue === $value)
			{
				return $case->getValue();
			}
		}

		throw new UnexpectedArgumentException('No enum element found for int value ' . $value);
	}

	private static function resolveAsIntValue(\ReflectionEnumUnitCase $case): int
	{
		$attributes = $case->getAttributes(AsInt::class);

		if (empty($attributes))
		{
			throw new SignException('Attribute ' . AsInt::class . ' not found for element ' . $case->getName());
		}

		if (count($attributes) > 1)
		{
			throw new SignException('Multiple ' . AsInt::class . ' attributes found for element ' . $case->getName());
		}

		$attributeInstance = $attributes[0]->newInstance();

		if (!$attributeInstance instanceof AsInt || !is_int($attributeInstance->value))
		{
			throw new SignException('The value of the AsInt attribute must be of type int for element ' . $case->getName());
		}

		return $attributeInstance->value;
	}
}
