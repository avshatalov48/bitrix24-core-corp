<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

use Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

class Text extends ContentBlock
{
	public const COLOR_GREEN = 'green';
	public const COLOR_BASE_50 = 'base_50';
	public const COLOR_BASE_70 = 'base_70';
	public const COLOR_BASE_90 = 'base_90';

	public const FONT_WEIGHT_NORMAL = 'normal';
	public const FONT_WEIGHT_MEDIUM = 'medium';
	public const FONT_WEIGHT_BOLD = 'bold';

	public const FONT_SIZE_XS = 'xs';
	public const FONT_SIZE_SM = 'sm';
	public const FONT_SIZE_MD = 'md';

	protected ?string $value = null;
	protected ?string $fontWeight = null;
	protected ?string $fontSize = null;
	protected ?string $color = null;

	public function getRendererName(): string
	{
		return 'TextBlock';
	}

	public function getColor(): ?string
	{
		return $this->color;
	}

	public function setColor(?string $color): self
	{
		$this->color = $color;

		return $this;
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

	public function getIsBold(): ?bool
	{
		return $this->fontWeight === self::FONT_WEIGHT_BOLD;
	}

	public function setIsBold(?bool $isBold): self
	{
		$this->setFontWeight($isBold ? self::FONT_WEIGHT_BOLD : self::FONT_WEIGHT_NORMAL);

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


	protected function getProperties(): array
	{
		return [
			'weight' => $this->getFontWeight(),
			'size' => $this->getFontSize(),
			'color' => $this->getColor(),
			'value' => html_entity_decode($this->getValue()),
		];
	}
}
