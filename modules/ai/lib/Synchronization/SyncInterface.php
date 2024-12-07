<?php

declare(strict_types = 1);

namespace Bitrix\AI\Synchronization;

interface SyncInterface
{
	/**
	 * Method for synchronisation items with current table by filter.
	 *
	 * @param array $items
	 * @param array $filter
	 *
	 * @return void
	 */
	public function sync(array $items, array $filter = []): void;
}
