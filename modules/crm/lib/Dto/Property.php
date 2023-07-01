<?php

namespace Bitrix\Crm\Dto;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\TextWithTranslationDto;
use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\Caster;
use Bitrix\Crm\Dto\Exception\DtoPropertyTypeIsUndefined;
use Bitrix\Main\ArgumentOutOfRangeException;

final class Property
{
	private \ReflectionProperty $property;

	private Dto $object;

	public function __construct(\ReflectionProperty $property, Dto $object)
	{
		$this->property = $property;
		$this->object = $object;
	}

	public function setValue($serializedValue)
	{
		if ($caster = $this->getCaster($this->object))
		{
			$typedValue = $caster->cast($serializedValue);
		}
		else
		{
			$typedValue = $serializedValue;
		}

		$this->property->setValue($this->object, $typedValue);
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
					$serializedValue[$k] = $item->jsonSerialize();
				}
				else
				{
					$serializedValue[$k] = $item;
				}
			}
		}
		elseif ($origValue instanceof Dto)
		{
			$serializedValue = $origValue->jsonSerialize();
		}
		else
		{
			$serializedValue = $origValue;
		}

		return $serializedValue;
	}

	private function getCaster(Dto $dto): ?\Bitrix\Crm\Dto\Caster
	{
		if (!$this->property->hasType())
		{
			throw new DtoPropertyTypeIsUndefined($dto, $this->getName());
		}
		$propertyType = $this->property->getType();
		$caster = $this->object->getCastByPropertyName($this->getName());

		if (!$caster)
		{
			$propertyTypeName = $propertyType->getName();
			if ($propertyType->isBuiltin())
			{
				switch ($propertyTypeName)
				{
					case 'string':
						$caster = new \Bitrix\Crm\Dto\Caster\StringCaster();
						break;
					case 'int':
						$caster = new \Bitrix\Crm\Dto\Caster\IntCaster();
						break;
					case 'bool':
						$caster = new \Bitrix\Crm\Dto\Caster\BoolCaster();
						break;
				}
				if (!$caster)
				{
					throw new ArgumentOutOfRangeException('Caster for type`' . $propertyTypeName . '` was not found');
				}
			}
			else // property type is a class
			{
				if ($propertyTypeName === TextWithTranslationDto::class)
				{
					$caster = new Caster\TextWithTranslationCaster();
				}
				else
				{
					$caster = new \Bitrix\Crm\Dto\Caster\ObjectCaster($propertyTypeName);
				}
			}
		}
		$caster->nullable($propertyType->allowsNull());

		return $caster;
	}
}
