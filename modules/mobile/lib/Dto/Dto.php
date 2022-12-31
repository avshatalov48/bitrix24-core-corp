<?php

namespace Bitrix\Mobile\Dto;

use Bitrix\Mobile\Dto\Caster\Caster;
use Bitrix\Mobile\Dto\Validator\Validator;
use Bitrix\Mobile\Dto\Transformer\Transformer;

abstract class Dto implements \JsonSerializable
{
	private ?array $casts = null;

	public function __construct(?array $fields = null)
	{
		if ($fields !== null)
		{
			$fields = $this->transformKeysOnDecode($fields);
			$properties = $this->getProperties();

			if (!$this->validate($fields, $properties))
			{
				throw new InvalidDtoException();
			}

			$this->initProperties($fields, $properties);
		}
	}

	/**
	 * @param array $fields
	 * @param Property[] $properties
	 */
	protected function initProperties(array $fields, array $properties)
	{
		foreach ($properties as $property)
		{
			$name = $property->getName();
			if (array_key_exists($name, $fields))
			{
				$property->setValue($this, $fields[$name]);
			}
		}
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}

	public function toArray(): array
	{
		$fields = [];

		foreach ($this->getProperties() as $property)
		{
			$name = $property->getName();
			$value = $property->getValue($this);

			$fields[$name] = $value;
		}

		return $this->transformKeysOnEncode($fields);
	}

	/**
	 * @return Property[]
	 */
	protected function getProperties(): array
	{
		$self = new \ReflectionClass($this);
		$properties = $self->getProperties(\ReflectionProperty::IS_PUBLIC);
		$result = [];

		foreach ($properties as $property)
		{
			$result[] = new Property($property, $this);
		}
		return $result;
	}

	protected function transformKeysOnEncode(array $fields): array
	{
		foreach ($this->getEncoders() as $transformer)
		{
			$fields = $transformer($fields);
		}

		return $fields;
	}

	protected function transformKeysOnDecode(array $fields): array
	{
		foreach ($this->getDecoders() as $transformer)
		{
			$fields = $transformer($fields);
		}

		return $fields;
	}

	/**
	 * @param array $fields
	 * @param Property[] $properties
	 * @return bool
	 */
	protected function validate(array $fields, array $properties): bool
	{
		foreach ($this->getValidators() as $validator)
		{
			if (!$validator($fields, $properties))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * @return Validator[]|callable[]
	 */
	protected function getValidators(): array
	{
		return [];
	}

	/**
	 * @return Transformer[]|callable[]
	 */
	protected function getEncoders(): array
	{
		return [];
	}

	/**
	 * @return Transformer[]|callable[]
	 */
	protected function getDecoders(): array
	{
		return [];
	}

	/**
	 * Method returns map, where key must relate to public property, and value specifies type of this property
	 * @return array<string, Caster> property name => typecaster object
	 */
	public function getCasts(): array
	{
		return [];
	}

	public function getCachedCasts(): array
	{
		if ($this->casts === null)
		{
			$this->casts = $this->getCasts();
		}

		return $this->casts;
	}
}
