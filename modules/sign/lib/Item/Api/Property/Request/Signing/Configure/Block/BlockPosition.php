<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure\Block;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;

class BlockPosition implements Contract\Item
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
		$this->top = $top;
		$this->left = $left;
		$this->width = $width;
		$this->height = $height;
		$this->widthPx = $widthPx;
		$this->heightPx = $heightPx;
		$this->page = $page;
	}

	public static function createFromBlockItemPosition(Item\Block\Position $position): static
	{
		return new static(
			$position->top,
			$position->left,
			$position->width,
			$position->height,
			$position->widthPx,
			$position->heightPx,
			$position->page,
		);
	}
}