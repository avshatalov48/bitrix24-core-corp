<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class Link extends ContentBlock implements TextPropertiesInterface
{
	use Actionable;
	use TextPropertiesMixin;

	protected ?string $value = null;
	protected ?string $icon = null;
	protected ?int $rowLimit = null;

	public function getRendererName(): string
	{
		return 'LinkBlock';
	}

	public function getValue(): ?string
	{
		return $this->value;
	}

	public function setValue(?string $value): self
	{
		$this->value = $value;

		return $this;
	}

	public function getIcon(): ?string
	{
		return $this->icon;
	}

	public function setIcon(?string $icon): self
	{
		$this->icon = $icon;

		return $this;
	}

	public function getRowLimit(): ?int
	{
		return $this->rowLimit;
	}

	public function setRowLimit(?int $rowLimit): self
	{
		$this->rowLimit = $rowLimit;

		return $this;
	}

	protected function getProperties(): array
	{
		return array_merge(
			$this->getTextProperties(),
			[
				'text' => html_entity_decode($this->getValue()),
				'bold' => $this->getIsBold(),
				'title' => $this->getTitle(),
				'icon' => $this->getIcon(),
				'action' => $this->getAction(),
				'rowLimit' => $this->getRowLimit(),
			]
		);
	}
}
