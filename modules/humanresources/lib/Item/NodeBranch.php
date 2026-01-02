<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Item\Collection\NodeCollection;

class NodeBranch implements Item
{
	public function __construct(
		public NodeCollection $nodeCollection,
		public ?string $rootTitle = '',
		public ?int $fromNodeId = null,
	) {}
}