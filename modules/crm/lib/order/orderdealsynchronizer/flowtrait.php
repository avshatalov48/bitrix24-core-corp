<?php

namespace Bitrix\Crm\Order\OrderDealSynchronizer;

/**
 * Trait for locking in flow the order and the deal.
 * Does not allow recursive save cycles.
 *
 * Typical example:
 * ```php
 * if ($this->isLockedDeal($dealId))
 * {
 *     return;
 * }
 *
 * try
 * {
 *     $this->lockDeal($dealId);
 *
 *     // process...
 * }
 * finally
 * {
 *     $this->unlockDeal($dealId);
 * }
 * ```
 */
trait FlowTrait
{
	/**
	 * At the start of synchronization, all participating entities are recorded here.
	 * After finished processing entity remove from flow.
	 *
	 * @var array
	 */
	private static $currentFlow = [];

	/**
	 * Checking whether the order has already been in the flow and whether it needs to be skipped?
	 *
	 * @param int $orderId
	 *
	 * @return bool
	 */
	protected function isLockedOrder(int $orderId): bool
	{
		$key = 'order' . $orderId;
		return isset(self::$currentFlow[$key]);
	}

	/**
	 * Add an order to the flow and skip it.
	 *
	 * @param int $orderId
	 *
	 * @return void
	 */
	protected function lockOrder(int $orderId): void
	{
		$key = 'order' . $orderId;
		self::$currentFlow[$key] = true;
	}

	/**
	 * Remove an order from flow.
	 *
	 * @param int $orderId
	 *
	 * @return void
	 */
	protected function unlockOrder(int $orderId): void
	{
		$key = 'order' . $orderId;
		unset(self::$currentFlow[$key]);
	}

	/**
	 * Checking whether the deal has already been in the flow and whether it needs to be skipped?
	 *
	 * @param int $dealId
	 *
	 * @return bool
	 */
	protected function isLockedDeal(int $dealId): bool
	{
		$key = 'deal' . $dealId;
		return isset(self::$currentFlow[$key]);
	}

	/**
	 * Add a deal to the flow and skip it.
	 *
	 * @param int $dealId
	 *
	 * @return void
	 */
	protected function lockDeal(int $dealId): void
	{
		$key = 'deal' . $dealId;
		self::$currentFlow[$key] = true;
	}

	/**
	 * Remove a deal from flow.
	 *
	 * @param int $dealId
	 *
	 * @return void
	 */
	protected function unlockDeal(int $dealId): void
	{
		$key = 'deal' . $dealId;
		unset(self::$currentFlow[$key]);
	}
}
