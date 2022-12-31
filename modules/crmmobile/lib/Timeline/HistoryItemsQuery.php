<?php

declare(strict_types = 1);

namespace Bitrix\CrmMobile\Timeline;

use Bitrix\Crm;
use Bitrix\Crm\Service\Timeline\Item;
use Bitrix\Crm\Service\Timeline\Repository;
use Bitrix\Main;
use Bitrix\Mobile;

final class HistoryItemsQuery extends Mobile\Query
{
	private const LIMIT = 100; // @todo tmp, decrease limit to 10-20 and add pagination?

	private Repository $repository;
	private Crm\Item $entity;
	private Pagination $pagination;

	public function __construct(Repository $repository, Crm\Item $entity, Pagination $pagination)
	{
		$this->repository = $repository;
		$this->entity = $entity;
		$this->pagination = $pagination;
	}

	/**
	 * @return Item[]
	 */
	public function execute(): array
	{
		$query = (new Repository\Query())
			->setOffsetId($this->pagination->offsetId)
			->setOffsetTime($this->pagination->offsetTime)
			->setFilter($this->getFilter())
			->setLimit(self::LIMIT);

		$result = $this->repository->getHistoryItemsPage($query);

		return [
			'items' => $result->getItems(),
			'pagination' => new Pagination($result->getOffsetId(), $result->getOffsetTime())
		];
	}

	private function getFilter(): array
	{
		return [];
	}
}
