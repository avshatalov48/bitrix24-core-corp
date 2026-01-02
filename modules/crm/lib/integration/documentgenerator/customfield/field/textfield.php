<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

class TextField extends AbstractField
{
	protected function getFieldType(): string
	{
		return FieldType::TEXT->value;
	}

	public function setPlaceholder(string $placeholder): static
	{
		return $this->setAttribute('placeholder', $placeholder);
	}

	public function setMaxLength(int $maxLength): static
	{
		return $this->setAttribute('maxlength', $maxLength);
	}
}
