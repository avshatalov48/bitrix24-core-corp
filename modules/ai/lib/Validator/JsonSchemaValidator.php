<?php

declare(strict_types=1);

namespace Bitrix\AI\Validator;

use InvalidArgumentException;

class JsonSchemaValidator
{
	public function validate($data, array $schema, string $path = ''): void
	{
		if (isset($schema['enum']) && is_array($schema['enum']))
		{
			if (!in_array($data, $schema['enum'], true))
			{
				$allowedValues = json_encode($schema['enum'], JSON_UNESCAPED_SLASHES);
				throw new InvalidArgumentException(
					"$path must be one of: $allowedValues, got " . json_encode($data),
				);
			}
		}

		// Check type
		if (isset($schema['type']))
		{
			$this->validateType($data, $schema['type'], $path);
		}

		// Check required (only for objects)
		if (isset($schema['required']) && is_array($schema['required']))
		{
			if (!is_array($data) && !is_object($data))
			{
				throw new InvalidArgumentException("$path must be an object (required fields check)");
			}
			foreach ($schema['required'] as $field)
			{
				if (!isset($data[$field]))
				{
					throw new InvalidArgumentException("$path.$field is required");
				}
			}
		}

		// Check properties (nested fields)
		if (isset($schema['properties']) && is_array($schema['properties']))
		{
			if (!is_array($data) && !is_object($data))
			{
				throw new InvalidArgumentException("$path must be an object (properties check)");
			}
			foreach ($schema['properties'] as $field => $fieldSchema)
			{
				if (array_key_exists($field, $data))
				{
					$this->validate($data[$field], $fieldSchema, "$path.$field");
				}
			}
		}

		// Check additionalProperties
		if (isset($schema['additionalProperties']) && $schema['additionalProperties'] === false)
		{
			if (!is_array($data) && !is_object($data))
			{
				throw new InvalidArgumentException("$path must be an object (additionalProperties check)");
			}
			$allowedFields = array_keys((array)$schema['properties'] ?? []);
			foreach ($data as $field => $value)
			{
				if (!in_array($field, $allowedFields))
				{
					throw new InvalidArgumentException("$path.$field is not allowed");
				}
			}
		}

		// Array items validation
		if (isset($schema['items']) && is_array($data))
		{
			foreach ($data as $index => $item)
			{
				$this->validate($item, $schema['items'], $path[$index] ?? '');
			}
		}
	}

	private function validateType($value, string $type, string $path): void
	{
		$valid = match ($type)
		{
			'string' => is_string($value),
			'number' => is_float($value) || is_int($value),
			'integer' => is_int($value),
			'boolean' => is_bool($value),
			'array' => is_array($value),
			'object' => is_array($value) || is_object($value),
			'null' => $value === null,
			default => throw new InvalidArgumentException("Unsupported type '$type' in schema at $path"),
		};

		if (!$valid)
		{
			throw new InvalidArgumentException(
				"$path must be of type $type, got " . gettype($value),
			);
		}
	}
}
