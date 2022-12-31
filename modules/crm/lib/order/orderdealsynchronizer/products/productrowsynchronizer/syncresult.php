<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer\Products\ProductRowSynchronizer;

use Bitrix\Sale\BasketItem;

/**
 * Synchronization result.
 */
class SyncResult
{
	/**
	 * Map created basket items
	 *
	 * @var array in format `['rowId' => 'basketItem']`
	 */
	private array $newBasketItems = [];

	/**
	 * A flag that determines whether synchronization has made changes.
	 *
	 * @var bool
	 */
	private bool $isChanged = false;

	/**
	 * Mark that synchronization has made changes.
	 *
	 * @return void
	 */
	public function markChanged(): void
	{
		$this->isChanged = true;
	}

	/**
	 * Did synchronization make changes?
	 *
	 * @return bool
	 */
	public function getChanged(): bool
	{
		return $this->isChanged;
	}

	/**
	 * Add new basket items.
	 *
	 * @param int $rowId related crm product row id.
	 * @param BasketItem $basketItem
	 *
	 * @return void
	 */
	public function addNewBasketItem(int $rowId, BasketItem $basketItem): void
	{
		$this->markChanged();
		$this->newBasketItems[$rowId] = $basketItem;
	}

	/**
	 * New basket items.
	 *
	 * @return array in format `['rowId' => 'basketItem']`
	 */
	public function getNewBasketItems(): array
	{
		return $this->newBasketItems;
	}
}
