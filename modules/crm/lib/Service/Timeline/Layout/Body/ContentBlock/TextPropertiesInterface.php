<?php

namespace Bitrix\Crm\Service\Timeline\Layout\Body\ContentBlock;

interface TextPropertiesInterface
{
	public function getColor(): ?string;
	public function setColor(?string $color): self;
	public function getIsBold(): ?bool;
	public function setIsBold(?bool $isBold): self;
	public function getFontWeight(): ?string;
	public function setFontWeight(?string $fontWeight): self;
	public function getFontSize(): ?string;
	public function setFontSize(?string $fontSize): self;
}
