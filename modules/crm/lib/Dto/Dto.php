<?php

namespace Bitrix\Crm\Dto;

use Bitrix\Crm\Dto\Exception\DtoPropertyNotFoundException;
use Bitrix\Main\ErrorCollection;

abstract class Dto implements \JsonSerializable, \Bitrix\Main\Type\Contract\Arrayable
{
	private ErrorCollection $validationErrors;

	public function __construct(?array $fields = null)
	{
		$this->validationErrors = new ErrorCollection();
		if ($fields !== null)
		{
			$this->validateFields($fields);
			$this->initProperties($fields);
		}
	}

	public function __clone(): void
	{
		$this->validationErrors = clone $this->validationErrors;

		$self = new \ReflectionClass($this);
		$publicFields = $self->getProperties(\ReflectionProperty::IS_PUBLIC);

		foreach ($publicFields as $property)
		{
			if (
				$property->isStatic()
				// when a typed non-initialized prop is accessed, we get runtime error
				|| ($property->hasType() && !$property->isInitialized($this))
			)
			{
				continue;
			}

			$value = $property->getValue($this);
			$name = $property->getName();

			if ($value instanceof Dto)
			{
				$this->$name = clone $value;
			}
			elseif (is_array($value) && current($value) instanceof Dto)
			{
				$this->$name = \Bitrix\Main\Type\Collection::clone($value);
			}
		}
	}

	public function toArray(): array
	{
		$fields = [];

		foreach ($this->getProperties() as $property)
		{
			$name = $property->getName();
			$value = $property->getValue($this);

			if (!is_null($value))
			{
				$fields[$name] = $value;
			}
		}

		return $fields;
	}

	/**
	 * @return mixed - mixed because of \Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\TextWithTranslationDto::jsonSerialize
	 */
	public function jsonSerialize(): mixed
	{
		return $this->toArray();
	}

	public function getValidationErrors(): ErrorCollection
	{
		return $this->validationErrors;
	}

	public function hasValidationErrors(): bool
	{
		return !$this->validationErrors->isEmpty();
	}

	public function __get(string $fieldName)
	{
		$this->throwFieldNotFoundException($fieldName);
	}

	public function __set(string $fieldName, $value): void
	{
		$this->throwFieldNotFoundException($fieldName);
	}

	public function __unset(string $fieldName): void
	{
		$this->throwFieldNotFoundException($fieldName);
	}

	/**
	 * @param array $fields
	 */
	protected function initProperties(array $fields)
	{
		foreach ($this->getProperties() as $property)
		{
			$name = $property->getName();
			if (array_key_exists($name, $fields))
			{
				$property->setValue($fields[$name]);

				$propertyValue = $this->$name;
				if ($propertyValue instanceof Dto)
				{
					$this->validationErrors->add($propertyValue->getValidationErrors()->toArray());
				}
				if (is_array($propertyValue))
				{
					foreach ($propertyValue as $propertyValueArrayItem)
					{
						if ($propertyValueArrayItem instanceof Dto)
						{
							$this->validationErrors->add($propertyValueArrayItem->getValidationErrors()->toArray());
						}
					}
				}
			}
		}
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

	/**
	 * @param array $fields
	 * @return void
	 */
	private function validateFields(array $fields): void
	{
		$validators = $this->getValidators($fields);

		$propertiesNames = array_map(fn($prop) => $prop->getName(), $this->getProperties());
		$validators[] =  new \Bitrix\Crm\Dto\Validator\HasNotRedundantFields($this, $propertiesNames);

		foreach ($validators as $validator)
		{
			$validationResult = $validator->validate($fields);
			if (!$validationResult->isSuccess())
			{
				$this->validationErrors->add($validationResult->getErrors());
			}
		}
	}

	/**
	 * @param array $fields
	 * @return Validator[]
	 */
	protected function getValidators(array $fields): array
	{
		return [];
	}

	public function getCastByPropertyName(string $propertyName): ?Caster
	{
		return null;
	}

	public function getName(): string
	{
		$classParts = explode('\\',static::class);

		return end($classParts);
	}

	private function throwFieldNotFoundException(string $propertyName): void
	{
		throw new DtoPropertyNotFoundException($this, $propertyName);
	}
}
