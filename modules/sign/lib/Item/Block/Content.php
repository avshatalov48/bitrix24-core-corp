<?php

namespace Bitrix\Sign\Item\Block;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Item;

class Content implements Contract\Item
{
	public ?string $text = null;
	public ?string $url = null;
	public ?Item\Fs\File $file = null;

	public function __construct(
		?string $text = null,
		?string $url = null,
		?Item\Fs\File $file = null,
	) {
		$this->file = $file;
		$this->url = $url;
		$this->text = $text;
	}
}