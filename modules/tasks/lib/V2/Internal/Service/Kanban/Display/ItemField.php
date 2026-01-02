<?php

namespace Bitrix\Tasks\V2\Internal\Service\Kanban\Display;

class ItemField
{
	public function __construct(
		private readonly string $code,
		private readonly string $title,
		private readonly string $categoryKey,
		private readonly bool $isSelected,
		private readonly bool $isDefault,
	)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}

	public function getTitle(): string
	{
		return $this->title;
	}

	public function toArray(): array
	{
		return [
			'id' => $this->code,
			'title' => $this->title,
			'categoryKey' => $this->categoryKey,
			'defaultValue' => $this->isDefault,
			'value' => $this->isSelected,
		];
	}
}