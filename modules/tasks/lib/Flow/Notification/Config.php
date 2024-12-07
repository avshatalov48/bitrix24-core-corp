<?php

namespace Bitrix\Tasks\Flow\Notification;

use Bitrix\Tasks\Flow\Notification\Config\Item;

class Config
{
	private int $flowId;
	/** @var Item[]  */
	private array $items = [];

	public function __construct(int $flowId, array $items = [])
	{
		$this->flowId = $flowId;

		foreach ($items as $item)
		{
			$this->addItem($item);
		}
	}

	public function getFlowId(): int
	{
		return $this->flowId;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	public function isEqual(Config $config): bool
	{
		if ($config->getFlowId() !== $this->flowId)
		{
			return false;
		}

		if (count($config->getItems()) !== count($this->items))
		{
			return false;
		}

		foreach ($config->getItems() as $itemToCompare)
		{
			$foundEqualItem = false;

			foreach ($this->items as $localItem)
			{
				if ($localItem->isEqual($itemToCompare))
				{
					$foundEqualItem = true;
				}
			}

			if ($foundEqualItem === false)
			{
				return false;
			}
		}

		return true;
	}

	public function toArray(): array
	{
		$items = [];
		foreach ($this->items as $item)
		{
			$items[] = $item->toArray();
		}

		return [
			'flowId' => $this->flowId,
			'items' => $items,
		];
	}


	private function addItem(Item $item): void
	{
		$this->items[] = $item;
	}
}