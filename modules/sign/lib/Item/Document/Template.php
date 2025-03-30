<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Type;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Attribute\Copyable;
use Bitrix\Sign\Item\TrackableItemTrait;

class Template implements Contract\Item, Contract\Item\ItemWithOwner, Contract\Item\TrackableItem
{
	use TrackableItemTrait;

	public function __construct(
		public string $title,
		public int $createdById,
		#[Copyable]
		public Type\Template\Status $status = Type\Template\Status::NEW,
		public Type\DateTime $dateCreate = new Type\DateTime(),
		public ?int $id = null,
		public ?string $uid = null,
		public ?Type\DateTime $dateModify = null,
		public ?int $modifiedById = null,
		public Type\Template\Visibility $visibility = Type\Template\Visibility::VISIBLE,
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