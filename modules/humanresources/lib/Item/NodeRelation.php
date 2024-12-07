<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Type\RelationEntityType;
use Bitrix\Main\Type\DateTime;

class NodeRelation implements Item
{
	public function __construct(
		public int $nodeId,
		public int $entityId,
		public RelationEntityType $entityType,
		public bool $withChildNodes = false,
		public ?int $id = null,
		public ?int $createdBy = null,
		public ?DateTime $createdAt = null,
		public ?DateTime $updatedAt = null,
		public ?Node $node = null,
	) {}
}