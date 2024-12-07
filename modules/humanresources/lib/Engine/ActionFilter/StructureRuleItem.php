<?php

namespace Bitrix\HumanResources\Engine\ActionFilter;

use Bitrix\HumanResources\Type\AccessibleItemType;

class StructureRuleItem
{
	public function __construct(
		public string $accessPermission,
		public ?AccessibleItemType $itemType = null,
		public ?string $itemIdRequestKey = null,
		public ?string $itemParentIdRequestKey = null,
	) {}
}