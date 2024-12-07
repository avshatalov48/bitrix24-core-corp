<?php

namespace Bitrix\Crm\Timeline\Entity\Repository;

use Bitrix\Crm\Activity\Entity\ConfigurableRestApp\Dto\RestAppLayoutBlocksDto;
use Bitrix\Crm\Timeline\Entity\RestAppLayoutBlocksTable;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ORM\Data\Result;
use Bitrix\Main\Web\Json;

class RestAppLayoutBlocksRepository
{
	public const MAX_LAYOUT_BLOCKS_COUNT = 20;

	public function __construct(
		protected RestAppLayoutBlocksTable|string $restAppLayoutBlocksTable = RestAppLayoutBlocksTable::class,
	)
	{
	}

	public function fetchLayoutBlocks(int $itemType, int $itemId, string $clientId): ?RestAppLayoutBlocksDto
	{
		$restAppLayout = $this->restAppLayoutBlocksTable::query()
			->setSelect(['LAYOUT'])
			->where('ITEM_TYPE', $itemType)
			->where('ITEM_ID', $itemId)
			->where('CLIENT_ID', $clientId)
			->setLimit(1)
			->fetchObject()
		;

		if ($restAppLayout === null)
		{
			return null;
		}

		$layoutBlocks = $restAppLayout->getLayout() ?? '{}';
		try
		{
			$layoutBlocks = (array)Json::decode($layoutBlocks);
		}
		catch (ArgumentException $e)
		{
			$layoutBlocks = [];
		}

		return new RestAppLayoutBlocksDto($layoutBlocks);
	}

	public function fetchLayoutBlocksForActivityItem(int $activityId, string $clientId): ?RestAppLayoutBlocksDto
	{
		return $this->fetchLayoutBlocks(
			$this->restAppLayoutBlocksTable::ACTIVITY_ITEM_TYPE,
			$activityId,
			$clientId,
		);
	}

	public function fetchLayoutBlocksForTimelineItem(int $timelineId, string $clientId): ?RestAppLayoutBlocksDto
	{
		return $this->fetchLayoutBlocks(
			$this->restAppLayoutBlocksTable::TIMELINE_ITEM_TYPE,
			$timelineId,
			$clientId,
		);
	}

	public function setLayoutBlocks(
		int $itemType,
		int $itemId,
		string $clientId,
		array $layout,
	): Result
	{
		$contentBlocksDto = new RestAppLayoutBlocksDto($layout);
		if ($contentBlocksDto->hasValidationErrors())
		{
			$errors = $contentBlocksDto->getValidationErrors()->toArray();

			return (new Result())->addErrors($errors);
		}

		$preparedLayout = Json::encode($contentBlocksDto);

		$restAppLayoutBlocks = $this->restAppLayoutBlocksTable::query()
			->setSelect(['*'])
			->where('ITEM_TYPE', $itemType)
			->where('ITEM_ID', $itemId)
			->where('CLIENT_ID', $clientId)
			->setLimit(1)
			->fetchObject()
		;

		if ($restAppLayoutBlocks === null)
		{
			return $this->restAppLayoutBlocksTable::add([
				'ITEM_TYPE' => $itemType,
				'ITEM_ID' => $itemId,
				'CLIENT_ID' => $clientId,
				'LAYOUT' => $preparedLayout,
			]);
		}

		return $restAppLayoutBlocks
			->setLayout($preparedLayout)
			->save()
		;
	}

	public function setLayoutBlocksForActivityItem(int $activityId, string $clientId, array $layout): Result
	{
		return $this->setLayoutBlocks(
			$this->restAppLayoutBlocksTable::ACTIVITY_ITEM_TYPE,
			$activityId,
			$clientId,
			$layout,
		);
	}

	public function setLayoutBlocksForTimelineItem(int $timelineId, string $clientId, array $layout): Result
	{
		return $this->setLayoutBlocks(
			$this->restAppLayoutBlocksTable::TIMELINE_ITEM_TYPE,
			$timelineId,
			$clientId,
			$layout,
		);
	}

	public function deleteLayoutBlocks(
		int $itemType,
		int $itemId,
		string $clientId,
	): Result|null
	{
		$restAppLayoutBlocks = $this->restAppLayoutBlocksTable::query()
			->setSelect(['ID'])
			->where('ITEM_TYPE', $itemType)
			->where('ITEM_ID', $itemId)
			->where('CLIENT_ID', $clientId)
			->setLimit(1)
			->fetchObject()
		;

		return $restAppLayoutBlocks?->delete();
	}

	public function deleteLayoutBlocksForActivityItem(
		int $activityId,
		string $clientId,
	): Result|null
	{
		return $this->deleteLayoutBlocks(
			$this->restAppLayoutBlocksTable::ACTIVITY_ITEM_TYPE,
			$activityId,
			$clientId,
		);
	}

	public function deleteLayoutBlocksForTimelineItem(
		int $timelineId,
		string $clientId,
	): Result|null
	{
		return $this->deleteLayoutBlocks(
			$this->restAppLayoutBlocksTable::TIMELINE_ITEM_TYPE,
			$timelineId,
			$clientId,
		);
	}

	public function loadForItems(array $items, int $itemType): array
	{
		$itemIds = array_column($items, 'ID');
		if (empty($itemIds))
		{
			return $items;
		}

		$restAppLayoutBlockList = $this->restAppLayoutBlocksTable::query()
			->setSelect(['*'])
			->where('ITEM_TYPE', $itemType)
			->whereIn('ITEM_ID', $itemIds)
			->fetchCollection()
		;

		if ($restAppLayoutBlockList->isEmpty())
		{
			return $items;
		}

		$restAppLayoutBlocksMap = [];
		foreach ($restAppLayoutBlockList as $restAppLayoutBlocks)
		{
			$itemId = $restAppLayoutBlocks->getItemId();
			$restAppLayoutBlocksMap[$itemId][] = $restAppLayoutBlocks;
		}

		foreach ($items as &$item)
		{
			$itemId = $item['ID'];
			$restAppLayoutBlocksForItem = $restAppLayoutBlocksMap[$itemId] ?? [];
			foreach ($restAppLayoutBlocksForItem as $restAppLayoutBlocks)
			{
				$layout = $restAppLayoutBlocks->getLayout();
				$layout = Json::decode($layout);

				$item['REST_APP_LAYOUT_BLOCKS'][] = [
					'ID' => $restAppLayoutBlocks->getId(),
					'ITEM_TYPE' => $restAppLayoutBlocks->getItemType(),
					'ITEM_ID' => $restAppLayoutBlocks->getItemId(),
					'CLIENT_ID' => $restAppLayoutBlocks->getClientId(),
					'LAYOUT_BLOCKS' => (new RestAppLayoutBlocksDto($layout))->blocks,
				];
			}
		}

		return $items;
	}

	public function rebind(
		int $fromItemType,
		int $fromItemId,
		int $toItemType,
		int $toItemId,
	): void
	{
		$restAppLayoutBlocksList = $this->restAppLayoutBlocksTable::query()
			->setSelect(['ID'])
			->where('ITEM_TYPE', $fromItemType)
			->where('ITEM_ID', $fromItemId)
			->fetchCollection()
		;

		if ($restAppLayoutBlocksList->isEmpty())
		{
			return;
		}

		foreach ($restAppLayoutBlocksList as $restAppLayoutBlocks)
		{
			$restAppLayoutBlocks
				->setItemId($toItemId)
				->setItemType($toItemType)
			;
		}

		$restAppLayoutBlocksList->save();
	}

	public function deleteByClientId(string $clientId): void
	{
		$ids = $this->restAppLayoutBlocksTable::query()
			->setSelect(['ID'])
			->where('CLIENT_ID', $clientId)
			->fetchCollection()
			->getIdList()
		;

		$this->restAppLayoutBlocksTable::deleteByIds($ids);
	}

	public function deleteByItem(int $itemId, int $itemTypeId): void
	{
		$ids = $this->restAppLayoutBlocksTable::query()
			->setSelect(['ID'])
			->where('ITEM_TYPE', $itemTypeId)
			->where('ITEM_ID', $itemId)
			->fetchCollection()
			->getIdList()
		;

		$this->restAppLayoutBlocksTable::deleteByIds($ids);
	}
}
