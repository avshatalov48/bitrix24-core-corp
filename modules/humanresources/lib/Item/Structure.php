<?php

namespace Bitrix\HumanResources\Item;

use Bitrix\HumanResources\Contract\Item;
use Bitrix\HumanResources\Type\StructureType;
use Bitrix\Main\Type\DateTime;

class Structure implements Item
{
	public const DEFAULT_STRUCTURE_XML_ID = 'COMPANY_STRUCTURE';

	public function __construct(
		public string $name,
		public ?StructureType $type = StructureType::DEFAULT,
		public ?int $id = null,
		public ?string $xmlId = null,
		public ?int $createdBy = null,
		public ?DateTime $createdAt = null,
		public ?DateTime $updatedAt = null,
	)
	{}
}