<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Timeline;

use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Repository;
use Bitrix\Mobile;

final class ScheduledItemsQuery extends Mobile\Query
{
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
		return $this->repository->getScheduledItems()->getItems();
	}
}