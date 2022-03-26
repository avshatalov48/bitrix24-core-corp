<?php

namespace Bitrix\Crm\Conversion;

use Bitrix\Crm\Security\EntityAuthorization;

class Scheme
{
	protected $items = [];
	protected $currentItemId;

	/**
	 * @param SchemeItem[] $items
	 */
	public function __construct(array $items)
	{
		$this->items = $items;
	}

	/**
	 * @return SchemeItem[]
	 */
	public function getItems(): array
	{
		return $this->items;
	}

	public function getCurrentItem(): ?SchemeItem
	{
		if (empty($this->items))
		{
			return null;
		}
		foreach ($this->items as $item)
		{
			if ((string)$this->currentItemId === (string)$item->getId())
			{
				return $item;
			}
		}

		return reset($this->items);
	}

	public function setCurrentItemId($currentItemId): self
	{
		$this->currentItemId = $currentItemId;

		return $this;
	}

	public function toJson(bool $isCheckPermissions = false): array
	{
		$result = [
			'currentItemId' => null,
			'items' => [],
		];

		$currentItem = $this->getCurrentItem();
		if (!$currentItem)
		{
			return $result;
		}

		$result = [
			'currentItemId' => $currentItem->getId(),
			'items' => [],
		];

		$permissions = [];
		foreach ($this->items as $item)
		{
			$isAllowed = true;
			if ($isCheckPermissions)
			{
				foreach ($item->getEntityTypeIds() as $entityTypeId)
				{
					if (!isset($permissions[$entityTypeId]))
					{
						$permissions[$entityTypeId] = EntityAuthorization::checkCreatePermission($entityTypeId);
					}
					if (!$permissions[$entityTypeId])
					{
						$isAllowed = false;
						break;
					}
				}
			}
			if ($isAllowed)
			{
				$result['items'][] = $item->toJson();
			}
		}

		return $result;
	}
}
