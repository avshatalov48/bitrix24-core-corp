<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Timeline;

use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Repository;
use Bitrix\Mobile;

final class PinnedItemsQuery extends Mobile\Query
{
	private const LIMIT = 3;

	private Repository $repository;

	public function __construct(Repository $repository)
	{
		$this->repository = $repository;
	}

	/**
	 * @return Item[]
	 */
	public function execute(): array
	{
		$query = (new Repository\Query())
			->setSearchForFixedItems(true)
			->setLimit(self::LIMIT);

		return $this->repository->getHistoryItemsPage($query)->getItems();
	}
}