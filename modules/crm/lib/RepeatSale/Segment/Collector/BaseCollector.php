<?php

namespace Bitrix\Crm\RepeatSale\Segment\Collector;

use Bitrix\Crm\RepeatSale\Segment\Data\LastSegmentData;
use Bitrix\Crm\RepeatSale\Segment\Data\SegmentData;
use Bitrix\Crm\RepeatSale\Segment\Data\SegmentDataInterface;
use Bitrix\Crm\RepeatSale\Segment\Data\WrongSegmentData;
use Bitrix\Crm\Service\Communication\Utils\Common;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Traits\Singleton;

abstract class BaseCollector
{
	use Singleton;

	protected int $limit = 50;
	protected bool $isOnlyCalc = false;

	public function setLimit(int $limit): self
	{
		$this->limit = $limit;

		return $this;
	}

	public function setIsOnlyCalc(bool $isOnlyCalc): BaseCollector
	{
		$this->isOnlyCalc = $isOnlyCalc;

		return $this;
	}

	public function getSegmentData(int $entityTypeId, ?int $lastItemId = null): SegmentDataInterface
	{
		$filter = [];
		if ($lastItemId > 0)
		{
			$filter['>ID'] = $lastItemId;
		}

		return $this->createSegmentData($entityTypeId, $filter);
	}

	protected function createSegmentData(int $entityTypeId, array $filter): SegmentDataInterface
	{
		if (!Common::isClientEntityTypeId($entityTypeId))
		{
			return new WrongSegmentData();
		}

		if ($entityTypeId === \CCrmOwnerType::Contact)
		{
			$ids = $this->getContactIds($filter);
		}
		else
		{
			$ids = $this->getCompanyIds($filter);
		}

		$items = $this->getItemsByIds($ids, $entityTypeId);
		if (empty($items))
		{
			$nextItemId = $this->getNextItemsMinId($entityTypeId, $filter);

			if ($nextItemId)
			{
				return new SegmentData(
					[],
					$entityTypeId,
					$nextItemId,
				);
			}

			$lastItemId = $filter['>ID'] ?? 0;

			return new LastSegmentData($entityTypeId, $lastItemId);
		}

		return new SegmentData(
			$items,
			$entityTypeId,
			array_pop($ids),
		);
	}

	abstract protected function getContactIds(array $filter): array;

	abstract protected function getCompanyIds(array $filter): array;

	private function getItemsByIds(array $ids, int $entityTypeId): array
	{
		if (empty($ids))
		{
			return [];
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);

		return $factory?->getItems([
			'select' => ['ID'],
			'filter' => [
				'@ID' => $ids,
			],
		]) ?? [];
	}

	abstract protected function getNextItemsMinId(int $entityTypeId, array $filter): ?int;
}
