<?php

namespace Bitrix\Crm\Comparer;

class Difference
{
	protected array $previousValues = [];
	protected array $currentValues = [];

	protected bool $isTreatingAbsentCurrentValueAsNotChangedEnabled = false;

	public function __construct(array $previousValues, array $currentValues)
	{
		$this->previousValues = $previousValues;
		$this->currentValues = $currentValues;
	}

	// arrays and objects are not compared identically
	public function isChanged(string $fieldName): bool
	{
		$previousValue = $this->getPreviousValue($fieldName);
		$currentValue = $this->getCurrentValue($fieldName);

		if (empty($previousValue) && empty($currentValue))
		{
			return false;
		}

		if ($this->isTreatingAbsentCurrentValueAsNotChangedEnabled && !array_key_exists($fieldName, $this->currentValues))
		{
			return false;
		}

		if (is_numeric($previousValue) && is_numeric($currentValue))
		{
			return ((float)$previousValue !== (float)$currentValue);
		}

		if (
			(is_object($previousValue) && is_object($currentValue))
			|| (is_array($previousValue) && is_array($currentValue))
		)
		{
			return ($previousValue != $currentValue);
		}

		return ($previousValue !== $currentValue);
	}

	/**
	 * @param string $fieldName
	 *
	 * @return mixed|null
	 */
	public function getPreviousValue(string $fieldName): mixed
	{
		return ($this->previousValues[$fieldName] ?? null);
	}

	public function setPreviousValue(string $fieldName, mixed $value): static
	{
		$this->previousValues[$fieldName] = $value;

		return $this;
	}

	/**
	 * @param string $fieldName
	 *
	 * @return mixed|null
	 */
	public function getCurrentValue(string $fieldName): mixed
	{
		return ($this->currentValues[$fieldName] ?? null);
	}

	public function setCurrentValue(string $fieldName, mixed $value): static
	{
		$this->currentValues[$fieldName] = $value;

		return $this;
	}

	/**
	 * Configure that a field is not considered changed if there is no such field in current values
	 *
	 * @param bool $isEnabled
	 *
	 * @return $this
	 */
	public function configureTreatingAbsentCurrentValueAsNotChanged(bool $isEnabled = true): self
	{
		$this->isTreatingAbsentCurrentValueAsNotChangedEnabled = $isEnabled;

		return $this;
	}
}
