<?php

namespace Bitrix\Crm\Comparer;

class Difference
{
	protected $previousValues = [];
	protected $currentValues = [];

	protected $isTreatingAbsentCurrentValueAsNotChangedEnabled = false;

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
	public function getPreviousValue(string $fieldName)
	{
		return ($this->previousValues[$fieldName] ?? null);
	}

	/**
	 * @param string $fieldName
	 *
	 * @return mixed|null
	 */
	public function getCurrentValue(string $fieldName)
	{
		return ($this->currentValues[$fieldName] ?? null);
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
