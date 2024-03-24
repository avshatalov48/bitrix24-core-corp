<?php

namespace Bitrix\Crm\Timeline\Rest\HistoryItem\ListParams;

final class Select
{
	public function __construct(private array $fields)
	{
	}

	public function getAllFields(): array
	{
		return $this->fields;
	}

	public function hasLayoutField(): bool
	{
		return in_array('LAYOUT', $this->fields, true);
	}

	public function hasBindings(): bool
	{
		return in_array('BINDINGS', $this->fields, true);
	}

	public function hasField(string $fieldName): bool
	{
		return in_array($fieldName, $this->fields, true);
	}
}