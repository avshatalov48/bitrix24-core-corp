<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class Link extends ContentBlock implements TextPropertiesInterface
{
	use Actionable;
	use TextPropertiesMixin;

	protected ?string $value = null;

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

	protected function getProperties(): array
	{
		return [
			'text' => html_entity_decode($this->getValue()),
			'bold' => $this->getIsBold(),
			'title' => $this->getTitle(),
			'action' => $this->getAction(),
		];
	}
}
