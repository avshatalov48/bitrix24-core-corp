<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Document\Upload;

use Bitrix\Sign\Contract;

class FileCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var File[] */
	private array $items;

	public function __construct(File ...$items)
	{
		$this->items = $items;
	}

	public function addItem(File $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}
}