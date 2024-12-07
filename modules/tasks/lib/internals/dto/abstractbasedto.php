<?php

namespace Bitrix\Tasks\Internals\Dto;

use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\Tasks\Internals\Trait\FromArrayTrait;
use Bitrix\Tasks\Internals\Trait\ToArrayTrait;
use Bitrix\Tasks\Internals\Attribute\CheckInterface;
use Bitrix\Tasks\Internals\Attribute\Primary;
use Bitrix\Tasks\Internals\Attribute\Required;
use Bitrix\Tasks\Internals\Attribute\Trait\PropertyTrait;
use Bitrix\Tasks\Internals\Attribute\Trait\PrimaryTrait;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

abstract class AbstractBaseDto implements Arrayable
{
	use FromArrayTrait;
	use ToArrayTrait;
	use PropertyTrait;
	use PrimaryTrait;

	protected static function modifyKeyFromArray(string $key): string
	{
		return $key;
	}

	protected static function modifyKeyToArray(string $key): string
	{
		return $key;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function __call(string $name, array $args)
	{
		$operation = substr($name, 0, 3);
		$property = lcfirst(substr($name, 3));

		if ($operation === 'set')
		{
			return $this->setProperty($property, $args);
		}

		if ($operation === 'has')
		{
			return $this->hasProperty($property);
		}

		$operation = substr($name, 0, 8);
		$property = lcfirst(substr($name, 8));

		if ($operation === 'validate')
		{
			$required = $args[0] ?? false;
			$this->validateProperty($property, $required);
		}

		return null;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function checkPrimary(): void
	{
		$reflection = new ReflectionClass($this);

		foreach ($reflection->getProperties() as $property)
		{
			if (
				!empty($property->getAttributes(Primary::class))
				&& !$this->hasProperty($property->getName())
			)
			{
				throw new InvalidArgumentException("Primary: field {$property->getName()} not found");
			}
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function validateIfSet(): void
	{
		$reflection = new ReflectionClass($this);

		foreach ($reflection->getProperties() as $property)
		{
			if ($property->isInitialized($this))
			{
				$this->validateProperty($property->getName());
			}
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function validate(): void
	{
		$reflection = new ReflectionClass($this);

		foreach ($reflection->getProperties() as $property)
		{
			$this->validateProperty($property->getName());
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected function validateProperty(string $propertyName, bool $required = false): void
	{
		try
		{
			$propertyReflection = new ReflectionProperty($this, $propertyName);
		}
		catch (ReflectionException $e)
		{
			throw new InvalidArgumentException("Unable to create property: {$e->getMessage()}");
		}

		if (!isset($this->{$propertyName}))
		{
			if ($required || !empty($propertyReflection->getAttributes(Required::class)))
			{
				throw new InvalidArgumentException("{$propertyName}: cannot be null");
			}

			return;
		}

		$propertyValue = $this->{$propertyName};

		if ($propertyReflection->hasDefaultValue() && $propertyReflection->getDefaultValue() === $propertyValue)
		{
			return;
		}

		foreach ($propertyReflection->getAttributes() as $attribute)
		{
			$attributeInstance = $attribute->newInstance();

			if ($attributeInstance instanceof CheckInterface && !$attributeInstance->check($propertyValue))
			{
				throw new InvalidArgumentException("{$propertyName}: unexpected value");
			}
		}
	}
}