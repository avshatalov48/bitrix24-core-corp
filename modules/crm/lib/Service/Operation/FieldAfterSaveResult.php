<?php

namespace Bitrix\Crm\Service\Operation;

use Bitrix\Main\Result;

class FieldAfterSaveResult extends Result
{
	protected $newValues = [];

	public function setNewValue(string $fieldName, $value): self
	{
		$this->newValues[$fieldName] = $value;

		return $this;
	}

	public function hasNewValues(): bool
	{
		return !empty($this->newValues);
	}

	public function getNewValues(): array
	{
		return $this->newValues;
	}
}