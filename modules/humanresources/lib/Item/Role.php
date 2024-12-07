<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Type;

class Role implements Item
{
	public function __construct(
		public ?string $name = null,
		public ?string $xmlId = null,
		public ?Type\RoleEntityType $entityType = null,
		public ?Type\RoleChildAffectionType $childAffectionType = null,
		public ?int $id = null,
		public ?int $priority = 100,
	)
	{}
}