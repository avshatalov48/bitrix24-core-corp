<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

class TextareaField extends TextField
{
	final protected function getFieldType(): string
	{
		return FieldType::TEXTAREA->value;
	}

	public function setRows(int $rows): static
	{
		return $this->setAttribute('rows', $rows);
	}

	public function setCols(int $cols): static
	{
		return $this->setAttribute('cols', $cols);
	}
}
