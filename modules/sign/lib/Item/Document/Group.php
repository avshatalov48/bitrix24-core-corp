<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Type;

final class Group implements Item
{
	public function __construct(
		public int $createdById,
		public Type\DateTime $dateCreate = new Type\DateTime(),
		public ?int $id = null,
		public ?Type\DateTime $dateModify = null,
	)
	{
	}
}