<?php

namespace Bitrix\Sign\Item\Blank;

use Bitrix\Sign\Contract;

class Resource implements Contract\Item
{
	public function __construct(
		public ?int $id,
		public int $blankId,
		public int $fileId,
	)
	{}
}
