<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Contract;
use Bitrix\Sign\Type;

class Template implements Contract\Item, Contract\Item\ItemWithOwner
{
	public function __construct(
		public string $title,
		public int $createdById,
		public Type\Template\Status $status = Type\Template\Status::NEW,
		public Type\DateTime $dateCreate = new Type\DateTime(),
		public ?int $id = null,
		public ?string $uid = null,
		public ?Type\DateTime $dateModify = null,
		public ?int $modifiedById = null,
		public Type\Template\Visibility $visibility = Type\Template\Visibility::VISIBLE,
	)
	{
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