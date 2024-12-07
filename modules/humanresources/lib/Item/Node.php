<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Type\NodeEntityType;
use Bitrix\Main\Type\DateTime;

class Node implements Item
{
	public function __construct(
		public string $name,
		public NodeEntityType $type,
		public int $structureId,
		public ?string $accessCode = null,
		public ?int $id = null,
		public ?int $parentId = null,
		public ?int $depth = null,
		public ?int $createdBy = null,
		public ?DateTime $createdAt = null,
		public ?DateTime $updatedAt = null,
		public ?string $xmlId = null,
		public ?bool $active = true,
		public ?bool $globalActive = true,
		public ?int $sort = 500,
		public ?string $description = null,
	) {}
}