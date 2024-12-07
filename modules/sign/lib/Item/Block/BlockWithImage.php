<?php

namespace Bitrix\Sign\Item\Block;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;

final class BlockWithImage implements Contract\Item
{
	public Item\Fs\File $image;
	public Item\Block $block;

	public function __construct(
		Item\Block $block,
		Item\Fs\File $image
	) {
		$this->block = $block;
		$this->image = $image;
	}
}