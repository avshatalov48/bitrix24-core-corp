<?php

namespace Bitrix\Tasks\Internals\Attribute\Trait;

use Bitrix\Tasks\Flow\Attribute\Instantiable;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

trait PropertyTrait
{
	/**
	 * @throws InvalidArgumentException
	 */
	protected function setProperty(string $property, array $args): static
	{
		$reflection = new ReflectionClass($this);
		if (!$reflection->hasProperty($property))
		{
			throw new InvalidArgumentException("Unable to set {$property}");
		}

		$reflectionProperty = $reflection->getProperty($property);
		if ($reflectionProperty->isReadOnly())
		{
			throw new InvalidArgumentException("Unable to set read-only property {$property}");
		}

		if (empty($args))
		{
			$this->buildInstance($property);
		}
		else
		{
			$this->{$property} = $args[0];
		}

		return $this;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected function buildInstance(string $property): static
	{
		try
		{
			$reflectionProperty = new ReflectionProperty($this, $property);
			if (empty($reflectionProperty->getAttributes(Instantiable::class)))
			{
				throw new InvalidArgumentException("Unable to create instance of not Instantiable: {$property}");
			}

			$class = $reflectionProperty->getType()?->getName();
			if (class_exists($class))
			{
				$classReflection = new ReflectionClass($class);
				if ($classReflection->isInstantiable())
				{
					$this->{$property} = $classReflection->newInstance();
				}
			}

			return $this;
		}
		catch (ReflectionException)
		{
			throw new InvalidArgumentException("Unable to create instance if {$property}");
		}
	}

	protected function hasProperty(string $property): bool
	{
		$reflection = new ReflectionClass($this);
		if (!$reflection->hasProperty($property))
		{
			return false;
		}

		return $reflection->getProperty($property)->isInitialized($this);
	}

	protected function isFilledProperty(string $property): bool
	{
		if (!$this->hasProperty($property))
		{
			return false;
		}

		$reflection = new ReflectionClass($this);
		$property = $reflection->getProperty($property);

		return $property->getValue($this) !== null;
	}
}
