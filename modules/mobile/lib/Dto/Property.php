<?php

namespace Bitrix\Mobile\Dto;

use Bitrix\Mobile\Dto\Caster\Caster;

final class Property
{
	private \ReflectionProperty $property;

	private Dto $object;

	public function __construct(\ReflectionProperty $property, Dto $object)
	{
		$this->property = $property;
		$this->object = $object;
	}

	public function setValue($object, $serializedValue)
	{
		if ($caster = $this->getCaster())
		{
			$typedValue = $caster->cast($serializedValue);
		}
		else
		{
			$typedValue = $serializedValue;
		}

		$this->property->setValue($object, $typedValue);
	}

	public function getName(): string
	{
		return $this->property->getName();
	}

	public function getValue($object = null)
	{
		$origValue = $this->property->getValue($object);
		if (is_array($origValue))
		{
			$serializedValue = [];
			foreach ($origValue as $k => $item)
			{
				if ($item instanceof Dto)
				{
					$serializedValue[$k] = $item->toArray();
				}
				else
				{
					$serializedValue[$k] = $item;
				}
			}
		}
		elseif ($origValue instanceof Dto)
		{
			$serializedValue = $origValue->toArray();
		}
		else
		{
			$serializedValue = $origValue;
		}

		return $serializedValue;
	}

	/**
	 * @return Caster|null
	 */
	private function getCaster(): ?Caster
	{
		$casts = $this->object->getCachedCasts();
		$caster = $casts[$this->getName()] ?? null;

		if ($caster)
		{
			return $caster;
		}

		if ($type = $this->property->getType())
		{
			return Type::makeCasterByPropertyType($type);
		}

		return null;
	}
}
