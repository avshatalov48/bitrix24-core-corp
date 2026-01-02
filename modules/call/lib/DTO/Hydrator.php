<?php

namespace Bitrix\Call\DTO;

use Bitrix\Main\Type\ParameterDictionary;

class Hydrator
{
	public function __construct(null|array|\stdClass|ParameterDictionary $fields = null)
	{
		if ($fields !== null)
		{
			if (is_array($fields))
			{
				$fields = (object) $fields;
			}
			$this->hydrate($fields);
		}
	}

	protected function hydrate(\stdClass|ParameterDictionary $fields): void
	{
		foreach ($fields as $property => $value)
		{
			if (property_exists($this, $property))
			{
				$this->validateAndSet($property, $value);
			}
		}
	}

	private function validateAndSet(string $property, $value): void
	{
		$expectedType = gettype($this->{$property});
		$actualType = gettype($value);

		if ($expectedType === $actualType)
		{
			$this->{$property} = $value;
		}
		else
		{
			switch ($expectedType)
			{
				case 'integer':
					$this->{$property} = (int)$value;
					break;
				case 'string':
					$this->{$property} = (string)$value;
					break;
				case 'boolean':
					$this->{$property} = (bool)$value;
					break;
				default:
					throw new \InvalidArgumentException("Invalid type for property {$property}. Expected {$expectedType}, got {$actualType}.");
			}
		}
	}
}
