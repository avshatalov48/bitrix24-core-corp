<?php

namespace Bitrix\Sign\Item\Api\Property\Request\Signing\Configure;

use Bitrix\Sign\Contract;

class FieldCollection implements Contract\ItemCollection, Contract\Item
{
	/** @var Field[] */
	private array $items;

	public function __construct(Field ...$items)
	{
		$this->items = $items;
	}

	public function addItem(Field $item): self
	{
		$this->items[] = $item;
		return $this;
	}

	public function toArray(): array
	{
		return $this->items;
	}

	public function findByTypeAndParty(string $type, int $party): ?Field
	{
		foreach ($this->items as $item)
		{
			if ($item->type === $type && $item->party === $party)
			{
				return $item;
			}
		}

		return null;
	}

	public function existWithName(string $name): bool
	{
		foreach ($this->toArray() as $item)
		{
			if ($item->name === $name)
			{
				return true;
			}
		}

		return false;
	}
}