<?php

namespace Bitrix\Sign\Item;

use Bitrix\Sign\Type;
use Bitrix\Sign\Contract;

class SignersList implements Contract\Item, Contract\Item\ItemWithOwner
{
	public function __construct(
		public string $title,
		public int $createdById,
		public ?int $id = null,
		public ?int $modifiedById = null,
		public Type\DateTime $dateCreate = new Type\DateTime(),
		public ?Type\DateTime $dateModify = null,
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
