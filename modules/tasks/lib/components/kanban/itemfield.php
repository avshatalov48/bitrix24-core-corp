<?php

namespace Bitrix\Tasks\Components\Kanban;

class ItemField
{
	private string $code;
	private string $title;
	private string $categoryKey;
	private bool $isSelected;
	private bool $isDefault;

	public function __construct(string $code, string $title, string $categoryKey, bool $isSelected, bool $isDefault)
	{
		$this->code = $code;
		$this->title = $title;
		$this->categoryKey = $categoryKey;
		$this->isSelected = $isSelected;
		$this->isDefault = $isDefault;
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