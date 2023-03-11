<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock\ExpandableList;

use Bitrix\Crm\Service\Timeline\Layout\Action;
use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

final class ExpandableListItemButton extends ContentBlock
{
	private ?string $text = null;
	private ?Action $action = null;

	public function getRendererName(): string
	{
		return 'ExpandableListItemButton';
	}

	public function getText(): ?string
	{
		return $this->text;
	}

	public function setText(?string $text): self
	{
		$this->text = $text;

		return $this;
	}

	public function getAction(): ?Action
	{
		return $this->action;
	}

	public function setAction(?Action $action): self
	{
		$this->action = $action;

		return $this;
	}

	protected function getProperties(): array
	{
		return [
			'text' => $this->getText(),
			'action' => $this->getAction(),
		];
	}
}
