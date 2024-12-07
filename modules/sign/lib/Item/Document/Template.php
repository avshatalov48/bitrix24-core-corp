<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Type;

class Template implements Item
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
	)
	{
	}
}