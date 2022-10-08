<?php

namespace Bitrix\Crm\Service\Timeline\Item;

class HistoryItemModel
{
	private array $fieldsValues = [];

	public static function createFromArray(array $data): self
	{
		return (new self())
			->setFieldValues($data)
		;
	}

	private function setFieldValues(array $values): self
	{
		$this->fieldsValues = $values;

		return $this;
	}

	public function get(string $fieldName)
	{
		return $this->fieldsValues[$fieldName] ?? null;
	}
}
