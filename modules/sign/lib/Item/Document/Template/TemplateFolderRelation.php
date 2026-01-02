<?php

namespace Bitrix\Sign\Item\Document\Template;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\Template\EntityType;

class TemplateFolderRelation implements Contract\Item
{
	public function __construct(
		public int $entityId,
		public EntityType $entityType,
		public int $createdById,
		public int $parentId = 0,
		public int $depthLevel = 0,
		public ?int $id = null,
	)
	{}
}