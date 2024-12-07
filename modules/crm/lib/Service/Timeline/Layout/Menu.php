<?php

namespace Bitrix\Crm\Service\Timeline\Layout;

use Bitrix\Crm\Service\Timeline\Layout\Menu\MenuItem;

class Menu extends Base
{
	protected ?MenuItem $deleteItem = null;
	protected ?MenuItem $pinItem = null;

	protected array $menuItems = [];

	protected int $currentSort = 0;
	protected const SORT_STEP = 100;

	public function addItem(string $id, MenuItem $item): self
	{
		if ($item->getSort() === null)
		{
			$item->setSort($this->currentSort);
			$this->currentSort += self::SORT_STEP;
		}

		$this->menuItems[$id] = $item;

		return $this;
	}

	/**
	 * @return MenuItem[]
	 */
	public function getItems(): array
	{
		return $this->menuItems;
	}

	/**
	 * @param MenuItem[] $menuItems
	 */
	public function setItems(array $menuItems): self
	{
		$this->menuItems = [];
		foreach ($menuItems as $id => $menuItem)
		{
			if (is_null($menuItem))
			{
				continue;
			}

			$this->addItem((string)$id, $menuItem);
		}

		return $this;
	}

	public function getItemById(string $id): ?MenuItem
	{
		return ($this->menuItems[$id] ?? null);
	}

	public function toArray(): array
	{
		return [
			'items' => $this->getItems(),
		];
	}
}
