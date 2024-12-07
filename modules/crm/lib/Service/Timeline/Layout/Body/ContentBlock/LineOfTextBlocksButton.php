<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;
use Bitrix\Crm\Service\Timeline\Layout\Mixin\Actionable;

class LineOfTextBlocksButton extends ContentBlock
{
	use Actionable;

	protected ?string $icon = null;
	protected ?string $title = null;

	public function getRendererName(): string
	{
		return 'LineOfTextBlocksButton';
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

	public function getTitle(): ?string
	{
		return $this->title;
	}

	public function setTitle(?string $title): self
	{
		$this->title = $title;

		return $this;
	}

	protected function getProperties(): array
	{
		return array_merge(
			[
				'icon' => $this->getIcon(),
				'action' => $this->getAction(),
				'title' => $this->getTitle(),
			]
		);
	}
}