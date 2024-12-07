<?php

namespace Bitrix\HumanResources\Attribute;

use \Attribute;
use Bitrix\HumanResources\Type\AccessibleItemType;

#[Attribute(flags: Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class StructureActionAccess
{
	public function __construct(
		public string $permission,
		public ?AccessibleItemType $itemType = null,
		public ?string $itemIdRequestKey = null,
		public ?string $itemParentIdRequestKey = null,
	) {}
}

