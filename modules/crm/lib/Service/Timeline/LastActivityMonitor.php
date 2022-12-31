<?php

namespace Bitrix\Crm\Service\Timeline;

use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Traits;
use Bitrix\Main\Application;

final class LastActivityMonitor
{
	use Traits\Singleton;

	/** @var Array<int, int[]> */
	private array $entityTypeIdToItemIds = [];

	/** @var string|TimelineTable */
	private $timelineDataManager = TimelineTable::class;
	/** @var string|ActivityTable */
	private $activityDataManager = ActivityTable::class;

	private function __construct()
	{
		Application::getInstance()->addBackgroundJob(fn() => $this->actualizeLastActivityInfo());
	}

	public function isTimelineChanged(ItemIdentifier $timelineOwner): bool
	{
		return isset($this->entityTypeIdToItemIds[$timelineOwner->getEntityTypeId()][$timelineOwner->getEntityId()]);
	}

	public function onTimelineChange(ItemIdentifier $timelineOwner): void
	{
		$this->entityTypeIdToItemIds[$timelineOwner->getEntityTypeId()][$timelineOwner->getEntityId()] =
			$timelineOwner->getEntityId()
		;
	}

	public function calculateLastActivityInfo(ItemIdentifier $timelineOwner): array
	{
		$lastTimelineEntry =
			$this->timelineDataManager::query()
				->setSelect([
					'AUTHOR_ID',
					'CREATED',
				])
				->where('BINDINGS.ENTITY_TYPE_ID', $timelineOwner->getEntityTypeId())
				->where('BINDINGS.ENTITY_ID', $timelineOwner->getEntityId())
				->setOrder([
					'CREATED' => 'DESC',
				])
				->setLimit(1)
				->fetchObject()
		;

		$lastActivity =
			$this->activityDataManager::query()
				->setSelect([
					'CREATED',
					'EDITOR_ID',
					'AUTHOR_ID',
					'RESPONSIBLE_ID',
					'PROVIDER_ID',
				])
				->where('BINDINGS.OWNER_TYPE_ID', $timelineOwner->getEntityTypeId())
				->where('BINDINGS.OWNER_ID', $timelineOwner->getEntityId())
				->setOrder([
					'CREATED' => 'DESC',
				])
				->setLimit(1)
				->fetchObject()
		;

		$timeFromEntry = $lastTimelineEntry ? $lastTimelineEntry->getCreated() : null;
		$timeFromActivity = $lastActivity ? $lastActivity->getCreated() : null;

		if (
			($timeFromEntry && !$timeFromActivity)
			|| ($timeFromEntry && $timeFromActivity && $timeFromEntry->getTimestamp() > $timeFromActivity->getTimestamp())
		)
		{
			return [
				$timeFromEntry,
				$lastTimelineEntry->getAuthorId(),
			];
		}

		if (
			(!$timeFromEntry && $timeFromActivity)
			|| ($timeFromEntry && $timeFromActivity && $timeFromEntry->getTimestamp() <= $timeFromActivity->getTimestamp())
		)
		{
			return [
				$timeFromActivity,
				ActivityController::resolveAuthorID($lastActivity->collectValues()),
			];
		}

		//neither any activity nor any timeline entry exists
		return [
			null,
			null,
		];
	}

	private function actualizeLastActivityInfo(): void
	{
		$factoryToItemsMap = $this->getFactoriesAndItems();
		foreach ($factoryToItemsMap as $factory)
		{
			/** @var Item[] $items */
			$items = $factoryToItemsMap[$factory];

			foreach ($items as $item)
			{
				$this->launchUpdateOperation($factory, $item);
			}
		}
	}

	/**
	 * @return \SplObjectStorage<Factory, Item[]>
	 */
	private function getFactoriesAndItems(): \SplObjectStorage
	{
		$factoryToItemsMap = new \SplObjectStorage();
		foreach ($this->entityTypeIdToItemIds as $entityTypeId => $itemIds)
		{
			$factory = $this->getSupportedFactory($entityTypeId);
			if (!$factory)
			{
				continue;
			}

			$items = $factory->getItems([
				'filter' => [
					'@' . Item::FIELD_NAME_ID => $itemIds,
				],
			]);

			$factoryToItemsMap[$factory] = $items;
		}

		return $factoryToItemsMap;
	}

	private function getSupportedFactory(int $entityTypeId): ?Factory
	{
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (
			$factory
			&& $factory->isLastActivitySupported()
		)
		{
			return $factory;
		}

		return null;
	}

	private function launchUpdateOperation(Factory $factory, Item $item): void
	{
		$context = clone Container::getInstance()->getContext();
		$context->setScope(Context::SCOPE_TASK);

		$operation = $factory->getUpdateOperation($item, $context);
		$operation
			//it's a system operation, data consistency will be harmed if any check fails
			->disableAllChecks()
			//to exclude any possibility of recursion
			->disableSaveToTimeline()
		;

		$operation->launch();
	}
}
