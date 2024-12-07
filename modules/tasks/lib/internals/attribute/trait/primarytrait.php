<?php

namespace Bitrix\Tasks\Internals\Attribute\Trait;

use Bitrix\Tasks\Internals\Attribute\Primary;
use OutOfBoundsException;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

trait PrimaryTrait
{
	/**
	 * @throws OutOfBoundsException
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
				throw new OutOfBoundsException("Unable to create reflection: {$e->getMessage()}");
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

		throw new OutOfBoundsException("Primary not found");
	}
}