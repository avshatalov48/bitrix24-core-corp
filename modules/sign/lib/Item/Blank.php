<?php

namespace Bitrix\Sign\Item;

use Bitrix\Main\Type\DateTime;
use Bitrix\Sign\Contract;
use Bitrix\Sign\Item\Fs\FileCollection;

class Blank implements Contract\Item, Contract\Item\ItemWithOwner, Contract\Item\TrackableItem
{
	use TrackableItemTrait;

	public function __construct(
		public ?string $title = null,
		public ?FileCollection $fileCollection = null,
		public ?string $status = null,
		public ?int $id = null,
		public ?string $converted = null,
		public ?DateTime $dateCreate = null,
		public ?BlockCollection $blockCollection = null,
		public ?string $scenario = null,
		public ?int $createdById = null,
		public bool $forTemplate = false,
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

	protected function getExcludedFromCopyProperties(): array
	{
		return [
			'id',
			'converted',
			'dateCreate',
			'blockCollection',
			'scenario',
			'createdById',
			'forTemplate',
		];
	}
}