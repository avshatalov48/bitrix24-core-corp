<?php

namespace Bitrix\StaffTrack\Internals;

use Bitrix\Main\Type;
use Bitrix\Main\Type\Contract\Arrayable;
use Bitrix\StaffTrack\Helper\DateHelper;
use Bitrix\StaffTrack\Internals\Attribute\CheckInterface;
use Bitrix\StaffTrack\Internals\Attribute\Nullable;
use Bitrix\StaffTrack\Internals\Attribute\Primary;
use Bitrix\StaffTrack\Internals\Attribute\Skip;
use Bitrix\StaffTrack\Internals\Exception\InvalidDtoException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use TypeError;

abstract class AbstractDto implements Arrayable
{
	public static function createFromArray(array $data): static
	{
		$dto = new static();
		$reflection = new ReflectionClass($dto);

		foreach ($data as $key => $value)
		{
			if (!$reflection->hasProperty($key))
			{
				continue;
			}

			$property = $reflection->getProperty($key);

			try
			{
				$dto->setValue($property, $value);
			}
			catch (TypeError)
			{

			}
		}

		return $dto;
	}

	protected function setValue(ReflectionProperty $property, mixed $value): void
	{
		$property->setValue($this, $value);
	}

	/**
	 * @throws InvalidDtoException
	 */
	public function __call(string $name, array $args)
	{
		$operation = substr($name, 0, 3);
		if ($operation === 'set')
		{
			$property = lcfirst(substr($name, 3));

			$reflection = new ReflectionClass($this);
			if (!$reflection->hasProperty($property))
			{
				throw new InvalidDtoException();
			}

			$this->{$property} = $args[0];

			return $this;
		}

		return null;
	}

	/**
	 * @throws InvalidDtoException
	 */
	public function validateAdd(): void
	{
		$this->validateAddProperties();

		foreach ($this as $value)
		{
			if ($value instanceof self)
			{
				$value->validateAdd();
			}
		}
	}

	/**
	 * @throws InvalidDtoException
	 */
	public function validateUpdate(): void
	{
		$this->validatePrimary();
		$this->validateUpdateProperties();

		foreach ($this as $value)
		{
			if ($value instanceof self)
			{
				$value->validateUpdate();
			}
		}
	}

	/**
	 * @throws InvalidDtoException
	 */
	protected function validateAddProperties(): void
	{
		$reflection = new ReflectionClass($this);
		foreach ($reflection->getProperties() as $property)
		{
			$propertyName = $property->getName();

			$this->validateProperty($propertyName);
		}
	}

	/**
	 * @throws InvalidDtoException
	 */
	protected function validateUpdateProperties(): void
	{
		$reflection = new ReflectionClass($this);
		foreach ($reflection->getProperties() as $property)
		{
			$propertyName = $property->getName();

			if (!isset($this->{$propertyName}))
			{
				continue;
			}

			$this->validateProperty($propertyName);
		}
	}

	/**
	 * @throws InvalidDtoException
	 */
	protected function validateProperty(string $propertyName): void
	{
		try
		{
			$propertyReflection = new ReflectionProperty($this, $propertyName);
		}
		catch (ReflectionException $e)
		{
			throw new InvalidDtoException("Could not create Dto: {$e->getMessage()}");
		}

		$isPropertyPrimary = !empty($propertyReflection->getAttributes(Primary::class));
		if ($isPropertyPrimary)
		{
			return;
		}

		if (!isset($this->{$propertyName}))
		{
			$cannotBeNullable = empty($propertyReflection->getAttributes(Nullable::class));
			if ($cannotBeNullable)
			{
				throw new InvalidDtoException("{$propertyName}: set value!");
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

			if ($attributeInstance instanceof Nullable)
			{
				continue;
			}

			if ($attributeInstance instanceof CheckInterface && !$attributeInstance->check($propertyValue))
			{
				$dump = var_export($propertyValue, true);
				throw new InvalidDtoException("{$propertyName}: unexpected value {$dump}");
			}
		}
	}

	/**
	 * @throws InvalidDtoException
	 */
	public function validateDelete(): void
	{
		$this->validatePrimary();
		foreach ($this as $value)
		{
			if ($value instanceof self)
			{
				$value->validateDelete();
			}
		}
	}

	/**
	 * @throws InvalidDtoException
	 */
	public function validatePrimary(): void
	{
		$primaryName =  $this->getPrimaryName();
		if (!isset($this->{$primaryName}))
		{
			throw new InvalidDtoException("Missing primary value");
		}

		try
		{
			$primaryReflection = new ReflectionProperty($this, $this->getPrimaryName());
		}
		catch (ReflectionException $e)
		{
			throw new InvalidDtoException("Could not create Dto: {$e->getMessage()}");
		}

		foreach ($primaryReflection->getAttributes() as $attribute)
		{
			$attributeInstance = $attribute->newInstance();

			if (
				$attributeInstance instanceof CheckInterface
				&& !$attributeInstance->check($this->{$primaryName})
			)
			{
				throw new InvalidDtoException("{$primaryName}: unexpected value {$this->{$primaryName}}");
			}
		}
	}

	public function toArray(bool $withDefault = true): array
	{
		$reflection = new ReflectionClass($this);
		$properties = $reflection->getProperties();
		$map = [];
		foreach ($properties as $property)
		{
			if (!empty($property->getAttributes(Skip::class)))
			{
				continue;
			}

			$name = $property->getName();

			if (
				isset($this->{$name})
				&& $property->hasDefaultValue()
				&& $property->getDefaultValue() === $this->{$name}
			)
			{
				if ($withDefault === true)
				{
					$map[$name] = $property->getDefaultValue();
				}
				else
				{
					continue;
				}
			}

			if (isset($this->{$name}))
			{
				$map[$name] = $this->{$name};
			}

			if (!empty($this->{$name}) && $property->getType()?->getName() === Type\Date::class)
			{
				$map[$name] = $this->{$name}->format(DateHelper::CLIENT_DATE_FORMAT);
			}

			if (!empty($this->{$name}) && $property->getType()?->getName() === Type\DateTime::class)
			{
				$map[$name] = DateHelper::getInstance()->getDateUtc($this->{$name})->format(DateHelper::CLIENT_DATETIME_FORMAT);
			}
		}

		return $map;
	}

	/**
	 * @throws InvalidDtoException
	 */
	public function getPrimaryName(): string
	{
		$reflection = new ReflectionClass($this);
		foreach ($reflection->getProperties() as $property)
		{
			$propertyName = $property->getName();
			try
			{
				$propertyReflection = new ReflectionProperty($this, $propertyName);
			}
			catch (ReflectionException $e)
			{
				throw new InvalidDtoException("Could not create Dto: {$e->getMessage()}");
			}

			foreach ($propertyReflection->getAttributes() as $attribute)
			{
				$attributeInstance = $attribute->newInstance();
				if ($attributeInstance instanceof Primary)
				{
					return $propertyName;
				}
			}
		}

		throw new InvalidDtoException("Primary not found");
	}
}