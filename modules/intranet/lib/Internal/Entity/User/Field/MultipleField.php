<?php

declare(strict_types=1);

namespace Bitrix\Intranet\Internal\Entity\User\Field;

/**
 * Decorator
 */
class MultipleField implements Field
{
	public function __construct(
		public readonly SingleField $field,
	)
	{}

	public function getId(): string
	{
		return $this->field->getId();
	}

	public function getTitle(): string
	{
		return $this->field->getTitle();
	}

	public function isEditable(): bool
	{
		return $this->field->isEditable();
	}

	public function isShowAlways(): bool
	{
		return $this->field->isShowAlways();
	}

	public function getValue(): array
	{
		if (!is_array($this->field->value))
		{
			return [];
		}

		return array_values(array_filter(
			$this->field->value,
			fn($value) => $this->field->isValid($value),
		));
	}

	public function isValid(mixed $value = null): bool
	{
		$values = $value ?? $this->field->value;

		if (!is_array($values))
		{
			return false;
		}

		foreach ($values as $fieldValue)
		{
			if (!$this->field->isValid($fieldValue))
			{
				return false;
			}
		}

		return true;
	}

	public function isMultiple(): bool
	{
		return true;
	}

	public function getType() : string
	{
		return $this->field->getType();
	}

	public function toArray()
	{
		$result = $this->field->toArray();
		$result['isMultiple'] = true;
		$result['value'] = $this->getValue();

		return $result;
	}
}
