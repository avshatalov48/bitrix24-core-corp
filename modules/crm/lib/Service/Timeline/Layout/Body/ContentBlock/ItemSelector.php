<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin;

class ItemSelector extends ContentBlock
{
	use Mixin\Actionable;

	protected ?array $value = null;
	protected ?array $valuesList = null;
	protected ?string $emptyState = null;
	protected ?string $selectorTitle = null;
	protected bool $compactMode = false;
	protected ?string $icon = null;

	public function getRendererName(): string
	{
		return 'ItemSelector';
	}

	public function getValuesList(): ?array
	{
		return $this->valuesList;
	}

	public function setValuesList(?array $valuesList): self
	{
		$this->valuesList = $valuesList;

		return $this;
	}

	public function getValue(): ?array
	{
		return $this->value;
	}

	public function setValue(?array $value): self
	{
		$this->value = $value;

		return $this;
	}

	public function getEmptyState(): ?string
	{
		return $this->emptyState;
	}

	public function setEmptyState(?string $emptyState): self
	{
		$this->emptyState = $emptyState;

		return $this;
	}

	public function getSelectorTitle(): ?string
	{
		return $this->selectorTitle;
	}

	public function setSelectorTitle(?string $selectorTitle): self
	{
		$this->selectorTitle = $selectorTitle;

		return $this;
	}

	public function setCompactMode(bool $value = true): self
	{
		$this->compactMode = $value;

		return $this;
	}

	public function getCompactMode(): bool
	{
		return $this->compactMode;
	}

	public function setIcon(?string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'selectorTitle' => $this->getSelectorTitle(),
			'emptyState' => $this->getEmptyState(),
			'valuesList' => $this->getValuesList(),
			'value' => $this->getValue(),
			'saveAction' => $this->getAction(),
			'compactMode' => $this->getCompactMode(),
			'icon' => $this->icon,
		];
	}
}
