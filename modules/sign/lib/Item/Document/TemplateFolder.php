<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Type;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\TrackableItemTrait;

class TemplateFolder implements Contract\Item, Contract\Item\ItemWithOwner, Contract\Item\TrackableItem
{
	use TrackableItemTrait;

	public function __construct(
		public string $title,
		public int $createdById,
		public ?int $id = null,
		public ?int $modifiedById = null,
		public ?Type\DateTime $dateModify = null,
		public Type\DateTime $dateCreate = new Type\DateTime(),
		public Type\Template\Visibility $visibility = Type\Template\Visibility::VISIBLE,
		public Type\Template\Status $status = Type\Template\Status::NEW,
	)
	{
		$this->initOriginal();
	}

	public function getId(): int
	{
		return $this->id ?? 0;
	}

	public function getOwnerId(): int
	{
		return $this->createdById ?? 0;
	}
}