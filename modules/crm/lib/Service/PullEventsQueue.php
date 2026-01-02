<?php

namespace Bitrix\Crm\Service;

use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Traits\BackgroundProcessor;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\ArrayHelper;

/**
 * @internal
 *
 * Deduplicates pull events that happen during the request and sends them all at once in the background.
 * Client code should usually use 'on...' methods to schedule an event, unless you have a more specific case.
 */
final class PullEventsQueue
{
	use BackgroundProcessor;

	private array $generalQueue = []; // queue for cases when we don't have data and need to fetch it from DB
	private array $itemUpdatesQueue = []; // queue for item updates, we have all the necessary data, no need for fetch

	public function onBadgeChange(ItemIdentifier $badgeOwner): void
	{
		$this->enqueue($badgeOwner);
	}

	public function onUncompletedActivityChange(ItemIdentifier $itemBoundToActivity): void
	{
		$this->enqueue($itemBoundToActivity);
	}

	public function onLightCounter(ItemIdentifier $itemBoundToActivity): void
	{
		$this->enqueue($itemBoundToActivity);
	}

	public function onEntityTypeDelete(int $entityTypeId): void
	{
		unset($this->generalQueue[$entityTypeId], $this->itemUpdatesQueue[$entityTypeId]);
	}

	public function scheduleItemUpdatedEvent(ItemIdentifier $item, array $pullItem, array $pullParams = []): void
	{
		if (isset($this->generalQueue[$item->getEntityTypeId()][$item->getEntityId()]))
		{
			// we will send event about this item in the background job, fetching all the recent data saved in DB

			return;
		}

		$previousEvent = $this->itemUpdatesQueue[$item->getEntityTypeId()][$item->getEntityId()] ?? null;
		if ($previousEvent === null)
		{
			$this->itemUpdatesQueue[$item->getEntityTypeId()][$item->getEntityId()] = [
				'pullItem' => $pullItem,
				'pullParams' => $pullParams,
			];

			$this->ensureProcessingScheduled();

			return;
		}

		$previousSkipCurrentUser = $previousEvent['pullParams']['SKIP_CURRENT_USER'] ?? true;
		if (!$previousSkipCurrentUser)
		{
			$pullParams['SKIP_CURRENT_USER'] = false;
		}

		$previousIgnoreDelay = $previousEvent['pullParams']['IGNORE_DELAY'] ?? false;
		if ($previousIgnoreDelay)
		{
			$pullParams['IGNORE_DELAY'] = true;
		}

		$this->itemUpdatesQueue[$item->getEntityTypeId()][$item->getEntityId()] = [
			'pullItem' => $pullItem,
			'pullParams' => $pullParams,
		];

		$this->ensureProcessingScheduled();
	}

	private function enqueue(ItemIdentifier $item): void
	{
		// item will be covered by general queue
		unset($this->itemUpdatesQueue[$item->getEntityTypeId()][$item->getEntityId()]);

		$this->generalQueue[$item->getEntityTypeId()][$item->getEntityId()] = $item->getEntityId();

		$this->ensureProcessingScheduled();
	}

	protected function getPriority(): int
	{
		// we generally want this service to run after agents and other jobs to minimize DB queries
		return Application::JOB_PRIORITY_LOW;
	}

	protected function process(): void
	{
		$this->processItemUpdatesQueue();
		$this->processGeneralQueue();
	}

	private function processItemUpdatesQueue(): void
	{
		$pullManager = PullManager::getInstance();
		foreach ($this->itemUpdatesQueue as $events)
		{
			foreach ($events as $singleEvent)
			{
				$pullManager->sendItemUpdatedEvent($singleEvent['pullItem'], $singleEvent['pullParams']);
			}
		}

		$this->itemUpdatesQueue = [];
	}

	private function processGeneralQueue(): void
	{
		$container = Container::getInstance();

		foreach ($this->generalQueue as $entityTypeId => $itemIdsOfSameType)
		{
			ArrayHelper::normalizeArrayValuesByInt($itemIdsOfSameType);
			if (empty($itemIdsOfSameType))
			{
				continue;
			}

			if ($entityTypeId === \CCrmOwnerType::Order && Loader::includeModule('sale'))
			{
				$this->sendOrdersUpdatedPushes($itemIdsOfSameType);
				continue;
			}

			$factory = $container->getFactory($entityTypeId);
			if (!$factory || !\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
			{
				continue;
			}

			$items = $factory->getItems([
				'select' => ['*'],
				'filter' => [
					'@' . Item::FIELD_NAME_ID => $itemIdsOfSameType,
				],
			]);

			foreach ($items as $singleItem)
			{
				$this->sendItemUpdatedPush($singleItem);
			}
		}

		$this->generalQueue = [];
	}

	private function sendItemUpdatedPush(Item $item): void
	{
		$entityTypeName = \CCrmOwnerType::ResolveName($item->getEntityTypeId());
		$kanbanEntity = Entity::getInstance($entityTypeName);
		if ($kanbanEntity)
		{
			PullManager::getInstance()->sendItemUpdatedEvent(
				$kanbanEntity->createPullItem($item->getCompatibleData()),
				[
					'TYPE' => $entityTypeName,
					'SKIP_CURRENT_USER' => false,
					'CATEGORY_ID' => $item->isCategoriesSupported() ? $item->getCategoryId() : null,
				],
			);
		}
	}

	private function sendOrdersUpdatedPushes(array $ids): void
	{
		$entity = Entity::getInstance(\CCrmOwnerType::OrderName);
		if (!$entity)
		{
			return;
		}

		$dbResult = $entity->getItems([
			'filter' => ['@ID' => $ids],
		]);

		$pullManager = PullManager::getInstance();

		while ($orderArray = $dbResult->Fetch())
		{
			$pullManager->sendItemUpdatedEvent(
				$entity->createPullItem($orderArray),
				[
					'TYPE' => \CCrmOwnerType::OrderName,
					'SKIP_CURRENT_USER' => false,
				],
			);
		}
	}
}
