<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

trait TextPropertiesMixin
{
	protected ?string $fontWeight = null;
	protected ?string $fontSize = null;
	protected ?string $color = null;
	protected ?string $title = null;
	protected ?string $decoration = null;

	public function getColor(): ?string
	{
		return $this->color;
	}

	public function setColor(?string $color): self
	{
		$this->color = $color;

		return $this;
	}

	public function getIsBold(): ?bool
	{
		if (is_null($this->fontWeight))
		{
			return null;
		}

		return $this->fontWeight === Text::FONT_WEIGHT_BOLD;
	}

	public function setIsBold(?bool $isBold): self
	{
		$this->setFontWeight($isBold ? Text::FONT_WEIGHT_BOLD : Text::FONT_WEIGHT_NORMAL);

		return $this;
	}

	public function getFontWeight(): ?string
	{
		return $this->fontWeight;
	}

	public function setFontWeight(?string $fontWeight): self
	{
		$this->fontWeight = $fontWeight;

		return $this;
	}

	public function getFontSize(): ?string
	{
		return $this->fontSize;
	}

	public function setFontSize(?string $fontSize): self
	{
		$this->fontSize = $fontSize;

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

	public function getDecoration(): ?string
	{
		return $this->decoration;
	}

	public function setDecoration(?string $decoration): self
	{
		$this->decoration = $decoration;

		return $this;
	}

	protected function getTextProperties(): array
	{
		return [
			'weight' => $this->getFontWeight(),
			'size' => $this->getFontSize(),
			'color' => $this->getColor(),
			'decoration' => $this->getDecoration(),
		];
	}
}
