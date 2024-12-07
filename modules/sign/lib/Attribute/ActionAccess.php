<?php

namespace Bitrix\Sign\Attribute;

use \Attribute;

#[Attribute(flags: Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ActionAccess
{
	public function __construct(
		public string $permission,
		public ?string $itemType = null,
		public ?string $itemIdRequestKey = null,
	)
	{}
}
