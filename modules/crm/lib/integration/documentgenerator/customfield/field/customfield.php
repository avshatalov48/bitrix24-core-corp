<?php

namespace Bitrix\Crm\Integration\DocumentGenerator\CustomField\Field;

class CustomField extends AbstractField
{
	protected string $html = '';

	final protected function getFieldType(): string
	{
		return FieldType::CUSTOM->value;
	}

	public function getHtml(): string
	{
		return $this->html;
	}

	public function setHtml(string $html): static
	{
		$this->html = $html;

		return $this;
	}

	public function toArray(): array
	{
		return array_merge(
			parent::toArray(),
			[
				'HTML' => $this->getHtml(),
			]
		);
	}
}
