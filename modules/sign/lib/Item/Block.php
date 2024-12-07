<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Contract;

class Block implements Contract\Item
{
	public function __construct(
		public int $party,
		public string $type,
		public string $code,
		public ?int $blankId = null,
		public ?Block\Position $position = null,
		public array $data = [],
		public ?int $id = null,
		public ?Block\Style $style = null,
		public ?FieldCollection $fields = null,
		public ?string $role = null,
	) {}
}
