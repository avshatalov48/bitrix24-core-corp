<?php

namespace Bitrix\Tasks\Flow\Notification\Command;

use Bitrix\Tasks\Flow\Notification\Config\Item;

class SaveConfigCommand
{
	private int $flowId;
	private array $items;
	private bool $forceSync;

	public function __construct(int $flowId, array $rowItems = [], $forceSync = false)
	{
		$this->flowId = $flowId;
		$this->items = $this->convertToObjects($rowItems);
		$this->forceSync = $forceSync;
	}

	public function getFlowId(): int
	{
		return $this->flowId;
	}

	public function getItems(): array
	{
		return $this->items;
	}

	public function isForceSync(): bool
	{
		return $this->forceSync;
	}

	private function convertToObjects(array $rowItems): array
	{
		$items = [];
		foreach ($rowItems as $rowItem)
		{
			if ($rowItem instanceof Item)
			{
				$items[] = $rowItem;
				continue;
			}

			$item = Item::toObject($rowItem);
			if ($item)
			{
				$items[] = $item;
			}
		}

		return $items;
	}
}