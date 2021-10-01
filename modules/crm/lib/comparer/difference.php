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

	public function isChanged(string $fieldName): bool
	{
		if (empty($this->getPreviousValue($fieldName)) && empty($this->getCurrentValue($fieldName)))
		{
			return false;
		}

		if ($this->isTreatingAbsentCurrentValueAsNotChangedEnabled && !array_key_exists($fieldName, $this->currentValues))
		{
			return false;
		}

		if (is_numeric($this->getPreviousValue($fieldName)) && is_numeric($this->getCurrentValue($fieldName)))
		{
			return ((float)$this->getPreviousValue($fieldName) !== (float)$this->getCurrentValue($fieldName));
		}

		return ($this->getPreviousValue($fieldName) !== $this->getCurrentValue($fieldName));
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
