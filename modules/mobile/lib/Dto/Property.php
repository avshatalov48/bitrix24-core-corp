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

	public function setValue($object, $serializedValue): void
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
	 * @return \ReflectionAttribute[]
	 */
	public function getAttributes(): array
	{
		return $this->property->getAttributes();
	}

	public function getCollectionAttribute() : ?\ReflectionAttribute
	{
		foreach ($this->getAttributes() as $attribute)
		{
			$typeName = $attribute->getName();
			if ($typeName !== \Bitrix\Mobile\Dto\Attributes\Collection::class)
			{
				continue;
			}

			return $attribute;
		}

		return null;
	}

	/**
	 * @return Caster|null
	 */
	private function getCaster(): ?Caster
	{
		$casts = $this->object->getCachedCasts();
		$caster = $casts[$this->getName()] ?? null;
		$attribute = $this->getCollectionAttribute();
		$type = $this->property->getType();

		if ($attribute)
		{
			$caster = Type::makeCollectionCasterFromAttributes($attribute, $type->allowsNull());
		}

		if ($caster)
		{
			return $caster;
		}

		if ($type)
		{
			return Type::makeCasterByPropertyType($type);
		}

		return null;
	}
}
