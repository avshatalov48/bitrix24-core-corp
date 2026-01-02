<?php

namespace Bitrix\Sign\Item\DocumentTemplateGrid;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type\DateTime;
use Bitrix\Sign\Type\Template\EntityType;
use Bitrix\Sign\Type\Template\Status;
use Bitrix\Sign\Type\Template\Visibility;

class Row implements Contract\Item
{
	public function __construct(
		public int $id,
		public string $title,
		public int $createdById,
		public EntityType $entityType,
		public ?string $uid = null,
		public ?bool $parentId = null,
		public ?int $modifiedById = null,
		public ?DateTime $dateModify = null,
		public DateTime $dateCreate = new DateTime(),
		public Visibility $visibility = Visibility::VISIBLE,
		public ?Status $status = null,
	) {}
}