<?php

namespace Bitrix\Crm\ItemMiniCard\Layout\Field;

final class CommonField extends AbstractField
{
	private array $values = [];

	public function __construct(
		public string $title,
	)
	{
	}

	public function addValue(string $value): self
	{
		$this->values[] = $value;

		return $this;
	}

	public function getName(): string
	{
		return 'CommonField';
	}

	public function getProps(): array
	{
		return [
			'title' => $this->title,
			'values' => $this->values,
		];
	}
}
