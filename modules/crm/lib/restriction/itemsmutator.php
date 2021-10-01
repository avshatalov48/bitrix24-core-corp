<?php

namespace Bitrix\Crm\Restriction;

class ItemsMutator
{
	protected $fieldsToShow;

	public function __construct(array $fieldsToShow)
	{
		$this->fieldsToShow = $fieldsToShow;
	}

	public function processItem(array $data, string $valueReplacer = null): array
	{
		$keys = array_diff(array_keys($data), $this->fieldsToShow);

		$replacedValues = array_fill_keys($keys, $valueReplacer);

		return array_merge($data, $replacedValues);
	}
}
