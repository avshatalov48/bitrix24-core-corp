<?php

namespace Bitrix\HumanResources\Internals\Attribute;

use Attribute;
use BackedEnum;
use InvalidArgumentException;
use ReflectionClass;
use Bitrix\HumanResources\Contract\Attribute\Validator;

#[Attribute]
class EnumValueValidator implements Validator
{
	/** @var class-string<BackedEnum> $enumClassName */
	public function __construct(
		private readonly string $enumClassName,
	)
	{
		if (!class_exists($this->enumClassName))
		{
			throw new InvalidArgumentException();
		}

		$reflection = new ReflectionClass($this->enumClassName);
		if (!$reflection->isEnum())
		{
			throw new InvalidArgumentException();
		}
	}

	public function validate(mixed $value): bool
	{
		return !empty($this->enumClassName::tryFrom($value));
	}
}
