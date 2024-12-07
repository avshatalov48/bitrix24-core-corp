<?php

namespace Bitrix\Sign\Item\Api\Document;

use Bitrix\Sign\Contract;

class RegisterRequest implements Contract\Item
{
	public function __construct(
		public string $lang,
		public string $scenario,
		public ?string $title = null
	) {}
}