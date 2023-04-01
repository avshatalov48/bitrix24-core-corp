<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class ValueChangeItem extends ContentBlock
{
	protected ?string $iconCode = null;
	protected ?string $text = null;
	protected ?string $pillText = null;

	public function getRendererName(): string
	{
		return 'ValueChangeItem';
	}

	protected function getProperties(): ?array
	{
		return [
			'iconCode' => $this->getIconCode(),
			'text' => $this->getText(),
			'pillText' => $this->getPillText(),
		];
	}

	public function getIconCode(): ?string
	{
		return $this->iconCode;
	}

	public function setIconCode(?string $iconCode): self
	{
		$this->iconCode = $iconCode;
		return $this;
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

	public function getPillText(): ?string
	{
		return $this->pillText;
	}

	public function setPillText(?string $badge): self
	{
		$this->pillText = $badge;
		return $this;
	}
}