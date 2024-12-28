<?php

namespace Bitrix\Sign\Item\Blank\Export;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Item\Block\Position;
use Bitrix\Sign\Item\Block\Style;

class PortableBlock implements Item
{
	public function __construct(
		public int $party,
		public string $type,
		public string $code,
		public ?Position $position = null,
		public ?Style $style = null,
		public ?string $role = null,
		public array $data = [],
	) {}
}