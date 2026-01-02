<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

class SelectField extends AbstractField
{
	protected array $options = [];
	protected bool $multiple = false;

	final protected function getFieldType(): string
	{
		return FieldType::SELECT->value;
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

	public function setMultiple(bool $multiple = true): static
	{
		$this->multiple = $multiple;

		return $this;
	}

	public function isMultiple(): bool
	{
		return $this->multiple;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'OPTIONS' => $this->getOptions(),
				'MULTIPLE' => $this->isMultiple(),
			]
		);
	}
}
