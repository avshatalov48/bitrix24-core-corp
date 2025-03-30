<?php

namespace Bitrix\Sign\Item\Document;

use Bitrix\Sign\Contract\Item;
use Bitrix\Sign\Item\TrackableItemTrait;
use Bitrix\Sign\Type;

final class Group implements Item, Item\TrackableItem
{
	use TrackableItemTrait;

	public function __construct(
		public int $createdById,
		public Type\DateTime $dateCreate = new Type\DateTime(),
		public ?int $id = null,
		public ?Type\DateTime $dateModify = null,
	)
	{
		$this->initOriginal();
	}

	protected function getExcludedFromCopyProperties(): array
	{
		return [
			'id',
			'dateModify',
		];
	}
}