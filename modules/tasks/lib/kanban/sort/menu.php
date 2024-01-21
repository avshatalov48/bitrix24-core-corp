<?php

namespace Bitrix\Tasks\Kanban\Sort;

use Bitrix\Tasks\Kanban\Sort\Item\ItemCollection;
use Bitrix\Tasks\Kanban\Sort\Item\MenuItem;

class Menu
{
	private ItemCollection $items;

	public function __construct(
		private string $id,
		private string $order = ''
	)
	{
		$this->init();
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function addItem(MenuItem $item): static
	{
		if (!$this->isCustomSort() && $item->isSub())
		{
			return $this;
		}

		$this->items->add($item);
		return $this;
	}

	public function toArray(): array
	{
		return array_map(function (MenuItem $item): array {
			$item->setTabId($this->getId());
			return $item->toArray();
		}, $this->items->toArray());
	}

	public function isCustomSort(): bool
	{
		return $this->order === MenuItem::SORT_ASC || $this->order === MenuItem::SORT_DESC;
	}

	private function init(): void
	{
		$this->items = new ItemCollection();
	}
}