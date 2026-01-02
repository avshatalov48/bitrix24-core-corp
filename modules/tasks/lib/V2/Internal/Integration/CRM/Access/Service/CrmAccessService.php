<?php

declare(strict_types=1);

namespace Bitrix\Tasks\V2\Internal\Integration\CRM\Access\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\CrmItem;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\CrmItemCollection;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Entity\LinkedType;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Repository\CrmItemRepositoryInterface;
use Bitrix\Tasks\V2\Internal\Integration\CRM\Repository\Mapper\CrmIdMapper;

class CrmAccessService
{
	public function __construct(
		private readonly CrmItemRepositoryInterface $crmItemRepository,
		private readonly CrmIdMapper $crmIdMapper,
	)
	{

	}

	public function getTasksCrmItems(array $taskIds, int $userId): CrmItemCollection
	{
		$itemMap = $this->crmItemRepository->getIdsByTaskIds($taskIds);
		$itemIds = array_merge(...$itemMap);
		$ids = $this->filterCrmItemsWithAccess($itemIds, $userId);

		$items = $this->crmItemRepository->getByIds($ids);

		$collection = new CrmItemCollection();
		foreach ($itemMap as $taskId => $taskItemIds)
		{
			$crmItems =
				$items
					->filter(static fn (CrmItem $item): bool => in_array($item->id, $taskItemIds, true))
					->cloneWith(['linkedEntityId' => $taskId, 'linkedEntityType' => LinkedType::Task->value])
			;

			$collection->merge($crmItems);
		}

		return $collection;
	}

	public function canChangeCrmItems(array $ids, int $userId, int $taskId): bool
	{
		$current = $this->crmItemRepository->getIdsByTaskId($taskId);

		$changed = array_merge(
			array_diff($ids, $current),
			array_diff($current, $ids)
		);

		if (empty($changed))
		{
			return true;
		}

		$withAccess = $this->filterCrmItemsWithAccess($changed, $userId);

		return count($changed) === count($withAccess);
	}

	public function filterCrmItemsWithAccess(array $ids, int $userId): array
	{
		if (!Loader::includeModule('crm'))
		{
			return [];
		}

		$ids = $this->groupByType($ids);

		return $this->getWithAccess($ids, $userId);
	}

	public function filterByTask(array $ids, int $taskId): array
	{
		$crmItemIds = $this->crmItemRepository->getIdsByTaskId($taskId);

		return array_filter($ids, static fn (string $id): bool => in_array($id, $crmItemIds, true));
	}

	private function groupByType(array $ids): array
	{
		$items = [];
		foreach ($ids as $id)
		{
			if (!is_string($id))
			{
				continue;
			}

			[$entityTypeId, $entityId] = $this->crmIdMapper->mapFromId($id);
			if ($entityId === null)
			{
				continue;
			}

			$items[$entityTypeId][] = $entityId;
		}

		return $items;
	}

	private function getWithAccess(array $ids, int $userId): array
	{
		$userPermissions = Container::getInstance()->getUserPermissions($userId)->item();

		$withAccess = [];
		foreach ($ids as $itemType => $itemIds)
		{
			$userPermissions->preloadPermissionAttributes($itemType, $itemIds);

			foreach ($itemIds as $itemId)
			{
				if (!$userPermissions->canRead($itemType, $itemId))
				{
					continue;
				}

				$withAccess[] = $this->crmIdMapper->mapToId($itemType, $itemId);
			}
		}

		return $withAccess;
	}
}
