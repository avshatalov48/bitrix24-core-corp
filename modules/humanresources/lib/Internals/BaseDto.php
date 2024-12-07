<?php

namespace Bitrix\HumanResources\Internals;

use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use InvalidArgumentException;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\HumanResources\Trait\ConvertToArray;
use Bitrix\HumanResources\Trait\CreateFromArray;
use Bitrix\HumanResources\Internals\Attribute\Primary;
use Bitrix\HumanResources\Internals\Attribute\Required;
use Bitrix\HumanResources\Contract\Attribute\Validator;

abstract class BaseDto implements Arrayable
{
	use CreateFromArray;
	use ConvertToArray;

	/**
	 * @throws InvalidArgumentException
	 */
	public function checkPrimary(): void
	{
		$reflection = new ReflectionClass($this);

		$primaryProperties = array_filter(
			$reflection->getProperties(),
			static fn(ReflectionProperty $property) => !empty($property->getAttributes(Primary::class))
		);

		if (empty($primaryProperties))
		{
			return;
		}

		foreach ($primaryProperties as $primaryProperty)
		{
			if (!$this->hasPropertyInitialized($primaryProperty->getName()))
			{
				throw new InvalidArgumentException("Primary: field {$primaryProperty->getName()} is not initialized");
			}
		}
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function validateInitializedProperties(): void
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
	protected function validateProperty(string $propertyName): void
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
			if (!empty($propertyReflection->getAttributes(Required::class)))
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

			if ($attributeInstance instanceof Validator && !$attributeInstance->validate($propertyValue))
			{
				throw new InvalidArgumentException("{$propertyName}: unexpected value");
			}
		}
	}

	protected function hasPropertyInitialized(string $property): bool
	{
		$reflection = new ReflectionClass($this);
		if (!$reflection->hasProperty($property))
		{
			return false;
		}

		return $reflection->getProperty($property)->isInitialized($this);
	}
}