<?php

namespace Bitrix\Sign\Item\Block;

use Bitrix\Sign\Contract;

class Position implements Contract\Item
{
	public float $top;
	public float $left;
	public float $width;
	public float $height;
	public int $widthPx;
	public int $heightPx;
	public int $page;

	public function __construct(
		float $top,
		float $left,
		float $width,
		float $height,
		int $widthPx,
		int $heightPx,
		int $page
	)
	{
		$this->page = $page;
		$this->heightPx = $heightPx;
		$this->widthPx = $widthPx;
		$this->height = $height;
		$this->width = $width;
		$this->left = $left;
		$this->top = $top;
	}
}