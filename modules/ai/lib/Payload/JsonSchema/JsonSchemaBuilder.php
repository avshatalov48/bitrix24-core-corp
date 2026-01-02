<?php declare(strict_types=1);

namespace Bitrix\AI\Payload\JsonSchema;

use Bitrix\AI\Payload\JsonSchema\Enum\SchemaType;
use Bitrix\Main\SystemException;

class JsonSchemaBuilder
{
	private array $schema;

	public function __construct()
	{
		$this->schema = [
			'type' => SchemaType::Object->value,
			'properties' => [],
			'required' => [],
			'additionalProperties' => false,
		];
	}

	protected function addProperty(
		string $name,
		SchemaType $type,
		bool $isRequired = false,
		?Constraints $constraints = null
	): self
	{
		$constraintsArray = $constraints?->toArray() ?? [];
		unset($constraintsArray['type']);

		$propertySchema = ['type' => $type->value, ...$constraintsArray];
		if (!$isRequired)
		{
			$propertySchema['type'] = [$propertySchema['type'], 'null'];
		}
		$this->schema['properties'][$name] = $propertySchema;

		$this->schema['required'][] = $name;

		return $this;
	}

	public function addStringProperty(string $name, bool $isRequired = false, ?Constraints $constraints = null): self
	{
		return $this->addProperty($name, SchemaType::String, $isRequired, $constraints);
	}

	public function addBoolProperty(string $name, bool $isRequired = false): self
	{
		return $this->addProperty($name, SchemaType::Boolean, $isRequired);
	}

	public function addNumberProperty(string $name, bool $isRequired = false, ?Constraints $constraints = null): self
	{
		return $this->addProperty($name, SchemaType::Number, $isRequired, $constraints);
	}

	public function addIntegerProperty(string $name, bool $isRequired = false, ?Constraints $constraints = null): self
	{
		return $this->addProperty($name, SchemaType::Integer, $isRequired, $constraints);
	}

	public function addArrayProperty(
		string $name,
		JsonSchemaBuilder $itemSchema,
		bool $isRequired = false
	): self
	{
		$constraints = new Constraints();
		$constraints->setItems([
			'type' => SchemaType::Object->value,
			'properties' => $itemSchema->getProperties(),
			'required' => $itemSchema->getRequired(),
			'additionalProperties' => false,
		]);
		$this->addProperty($name, SchemaType::Array, $isRequired, $constraints);

		return $this;
	}

	/**
	 * @param string $name
	 * @param JsonSchemaBuilder[] $itemSchema
	 * @param bool $isRequired
	 *
	 * @return void
	 */
	public function addAnyOfProperty(
		string $name,
		array $itemSchema,
		bool $isRequired = false
	): self
	{
		$constraints = new Constraints();
		$constraints->setItems([
			SchemaType::AnyOf->value => array_map(
				static fn ($itemSchema) => $itemSchema->getSchema(), $itemSchema
			)
		]);
		$this->addProperty($name, SchemaType::Array, $isRequired, $constraints);

		return $this;
	}

	protected function getProperties(): array
	{
		return $this->schema['properties'];
	}

	protected function getRequired(): array
	{
		return $this->schema['required'];
	}

	protected function getSchema(): array
	{
		return $this->schema;
	}

	/**
	 * Retrieves a schema for structured output based on the provided name.
	 *
	 * This method validates that the given `$name` matches a specific pattern (`^[a-zA-Z0-9_-]+$`),
	 * and throws an exception if it doesn't. It then returns an array containing the name, the
	 * strict flag, and the valid schema for openai https://platform.openai.com/docs/guides/structured-outputs?format=parse.
	 *
	 * @param string $name The name to be validated and included in the returned schema.
	 *
	 * @return array An associative array containing the name, strict flag, and schema.
	 *
	 * @throws SystemException If the $name does not match the expected pattern.
	 */
	public function build(string $name): array
	{
		if (!preg_match('/^[a-zA-Z0-9_-]+$/', $name))
		{
			throw new SystemException('String does not match pattern. Expected a string that matches the pattern \'^[a-zA-Z0-9_-]+$\'.');
		}

		return [
			'name' => $name,
			'strict' => true,
			'schema' => $this->getSchema(),
		];
	}
}