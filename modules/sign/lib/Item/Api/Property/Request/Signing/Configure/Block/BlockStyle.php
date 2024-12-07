<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Block;

use Bitrix\Sign\Contract;

class BlockStyle implements Contract\Item
{
	public ?string $color = null;
	public ?string $padding = null;
	public ?string $fontSize = null;
	public ?string $fontFamily = null;
	public ?string $fontWeight = null;
	public ?string $fontStyle = null;
	public ?string $textDecoration = null;
	public ?string $textAlign = null;
	public ?string $backgroundColor = null;
	public ?string $backgroundPosition = null;

	public function __construct(
		?string $color = null,
		?string $padding = null,
		?string $fontSize = null,
		?string $fontFamily = null,
		?string $fontWeight = null,
		?string $fontStyle = null,
		?string $textDecoration = null,
		?string $textAlign = null,
		?string $backgroundColor = null,
		?string $backgroundPosition = null,
	)
	{
		$this->color = $color;
		$this->padding = $padding;
		$this->fontSize = $fontSize;
		$this->fontFamily = $fontFamily;
		$this->fontWeight = $fontWeight;
		$this->fontStyle = $fontStyle;
		$this->textDecoration = $textDecoration;
		$this->textAlign = $textAlign;
		$this->backgroundColor = $backgroundColor;
		$this->backgroundPosition = $backgroundPosition;
	}

	public static function createFromBlockItemStyle(\Bitrix\Sign\Item\Block\Style $style): static
	{
		return new static(
			$style->color,
			$style->padding,
			$style->fontSize,
			$style->fontFamily,
			$style->fontWeight,
			$style->fontStyle,
			$style->textDecoration,
			$style->textAlign,
			$style->backgroundColor,
			$style->backgroundPosition,
		);
	}
}