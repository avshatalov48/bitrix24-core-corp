<?php

namespace Bitrix\Sign\Item\Block;

use Bitrix\Sign\Contract\Item;

class Style implements Item
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
		$this->backgroundPosition = $backgroundPosition;
		$this->backgroundColor = $backgroundColor;
		$this->textAlign = $textAlign;
		$this->textDecoration = $textDecoration;
		$this->fontStyle = $fontStyle;
		$this->fontWeight = $fontWeight;
		$this->fontFamily = $fontFamily;
		$this->fontSize = $fontSize;
		$this->padding = $padding;
		$this->color = $color;
	}
}