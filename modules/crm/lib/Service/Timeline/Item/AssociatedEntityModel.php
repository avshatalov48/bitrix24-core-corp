<?php

namespace Bitrix\Crm\Service\Timeline\Item;

use Bitrix\Main\Type\Contract\Arrayable;

class AssociatedEntityModel implements Arrayable
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

	public function toArray(): array
	{
		return $this->fieldsValues;
	}
}
