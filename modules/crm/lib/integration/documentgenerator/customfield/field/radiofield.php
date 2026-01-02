<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

class RadioField extends AbstractField
{
	protected array $options = [];

	final protected function getFieldType(): string
	{
		return FieldType::RADIO->value;
	}

	public function setOptions(array $options): static
	{
		$this->options = $options;

		return $this;
	}

	public function getOptions(): array
	{
		return $this->options;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'OPTIONS' => $this->getOptions(),
			]
		);
	}
}
