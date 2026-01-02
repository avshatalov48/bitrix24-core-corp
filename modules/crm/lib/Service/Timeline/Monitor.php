<?php

namespace Bitrix\Crm\Service\Timeline;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\EO_Activity;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\Entity\EO_Timeline;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Traits;
use Bitrix\Main\InvalidOperationException;
use Bitrix\Main\ORM\Query\Filter\ConditionTree;

final class Monitor
{
	use Traits\Singleton;
	use Traits\BackgroundProcessor;

	/** @var array<int, array<int, ItemIdentifier>> - [entityTypeId => [itemId => ItemIdentifier]] */
	private array $changes = [];
	/** @var array<int, array<int, Item>> - [entityTypeId => [itemId => Item]] */
	private array $loadedTimelineOwners = [];
	private array $suitableTimelineEntriesCache = [];

	// no type hints for managers since they can be mocked in tests

	/**
	 * @var string|TimelineTable
	 * @noinspection PhpMissingFieldTypeInspection
	 */
	private $timelineDataManager = TimelineTable::class;
	/**
	 * @var string|ActivityTable
	 * @noinspection PhpMissingFieldTypeInspection
	 */
	private $activityDataManager = ActivityTable::class;
	/**
	 * @var string|IncomingChannelTable
	 * @noinspection PhpMissingFieldTypeInspection
	 */
	private $incomingChannelDataManager = IncomingChannelTable::class;

	public function onTimelineEntryAddIfSuitable(ItemIdentifier $timelineOwner, int $timelineEntryId): void
	{
		if ($this->isTimelineEntrySuitable($timelineOwner, $timelineEntryId))
		{
			$this->onTimelineEntryAdd($timelineOwner);
		}
	}

	public function onTimelineEntryAdd(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner);
	}

	public function onTimelineEntryRemoveIfSuitable(ItemIdentifier $timelineOwner, int $timelineEntryId): void
	{
		// no-op, last activity cant decrease
 	}

	public function onTimelineEntryRemove(ItemIdentifier $timelineOwner): void
	{
		// no-op, last activity cant decrease
	}

	public function onActivityAddIfSuitable(ItemIdentifier $timelineOwner, int $activityId): void
	{
		if ($this->isActivitySuitable($activityId))
		{
			$this->onActivityAdd($timelineOwner);
		}
	}

	public function onActivityAdd(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner);
	}

	public function onActivityRemoveIfSuitable(ItemIdentifier $timelineOwner, int $activityId): void
	{
		// no-op, last activity cant decrease
	}

	public function onActivityRemove(ItemIdentifier $timelineOwner): void
	{
		// no-op, last activity cant decrease
	}

	public function onEntityTypeDelete(int $entityTypeId): void
	{
		unset($this->changes[$entityTypeId], $this->loadedTimelineOwners[$entityTypeId]);
	}

	/**
	 * @deprecated You don't to call this method to sync badges in realtime anymore. Badges API will do it for you
	 * automatically.
	 *
	 * If you still want to manually trigger sync for some reason.
	 * Do it only if you fully understand what you are doing.
	 * @see \Bitrix\Crm\Service\PullEventsQueue::onBadgeChange
	 */
	public function onBadgesSync(ItemIdentifier $timelineOwner): void
	{
	}

	private function upsertChange(ItemIdentifier $timelineOwner): void
	{
		$this->changes[$timelineOwner->getEntityTypeId()][$timelineOwner->getEntityId()] = $timelineOwner;
		$this->ensureProcessingScheduled();
	}

	protected function process(): void
	{
		$this->preloadTimelineOwners();

		$lastActivityService = Container::getInstance()->getLastActivity();

		foreach ($this->changes as $entityTypeId => $itemIdentifiers)
		{
			if (!$this->isEntitySupported($entityTypeId))
			{
				continue;
			}

			foreach ($itemIdentifiers as $singleIdentifier)
			{
				[$lastActivityTime, $lastActivityBy] = $this->calculateLastActivityInfo($singleIdentifier);

				$lastActivityTime ??= $this->getTimelineOwner($singleIdentifier)?->getCreatedTime();
				$lastActivityBy ??= $this->getTimelineOwner($singleIdentifier)?->getCreatedBy();

				if ($lastActivityTime !== null && $lastActivityBy !== null)
				{
					$lastActivityService->set($singleIdentifier, $lastActivityTime, $lastActivityBy);
				}
			}
		}

		$this->changes = [];
	}

	private function isEntitySupported(int $entityTypeId): bool
	{
		return $this->getSupportedFactory($entityTypeId) !== null;
	}

	private function getSupportedFactory(int $entityTypeId): ?Factory
	{
		if (!\CCrmOwnerType::isUseFactoryBasedApproach($entityTypeId))
		{
			return null;
		}

		$factory = Container::getInstance()->getFactory($entityTypeId);

		if (!$factory || !$factory->isLastActivitySupported())
		{
			return null;
		}

		return $factory;
	}

	private function preloadTimelineOwners(): void
	{
		foreach ($this->changes as $entityTypeId => $identifiersOfSameType)
		{
			$notLoadedIds = array_keys(
				array_diff_key(
					$identifiersOfSameType,
					$this->loadedTimelineOwners[$entityTypeId] ?? []
				),
			);
			if (empty($notLoadedIds))
			{
				continue;
			}

			$items = $this->loadTimelineOwners($entityTypeId, $notLoadedIds);

			$this->loadedTimelineOwners[$entityTypeId] ??= [];
			foreach ($items as $itemId => $item)
			{
				$this->loadedTimelineOwners[$entityTypeId][$itemId] = $item;
			}
		}
	}

	private function loadTimelineOwners(int $entityTypeId, array $itemIds): array
	{
		if (empty($itemIds))
		{
			return [];
		}

		$factory = $this->getSupportedFactory($entityTypeId);
		if (!$factory)
		{
			return [];
		}

		$items = $factory->getItems([
			'select' => [
				Item::FIELD_NAME_ASSIGNED,
				Item::FIELD_NAME_CREATED_BY,
				Item::FIELD_NAME_CREATED_TIME,
			],
			'filter' => [
				'@' . Item::FIELD_NAME_ID => $itemIds,
			],
		]);

		$result = [];
		foreach ($items as $item)
		{
			$result[$item->getId()] = $item;
		}

		return $result;
	}

	private function getTimelineOwner(ItemIdentifier $identifier): ?Item
	{
		$preloaded = $this->loadedTimelineOwners[$identifier->getEntityTypeId()][$identifier->getEntityId()] ?? null;
		if ($preloaded)
		{
			return $preloaded;
		}

		$items = $this->loadTimelineOwners($identifier->getEntityTypeId(), [$identifier->getEntityId()]);
		$item = $items[$identifier->getEntityId()] ?? null;
		if (!$item)
		{
			return null;
		}

		$this->loadedTimelineOwners[$identifier->getEntityTypeId()] ??= [];
		$this->loadedTimelineOwners[$identifier->getEntityTypeId()][$identifier->getEntityId()] = $item;

		return $item;
	}

	private function calculateLastActivityInfo(ItemIdentifier $timelineOwner): array
	{
		$lastTimelineEntry = $this->getLastRelevantTimelineEntry($timelineOwner);
		$lastActivity = $this->getLastRelevantActivity($timelineOwner);

		$timeFromEntry = $lastTimelineEntry?->requireCreated();
		$timeFromActivity = $lastActivity?->requireCreated();

		if (!$timeFromEntry && !$timeFromActivity)
		{
			//neither any activity nor any timeline entry exists
			return [
				null,
				null,
			];
		}

		if (
			($timeFromEntry && !$timeFromActivity)
			|| (
				$timeFromEntry
				&& $timeFromActivity
				&& $timeFromEntry->getTimestamp() > $timeFromActivity->getTimestamp()
			)
		)
		{
			return [
				$timeFromEntry,
				$lastTimelineEntry->getAuthorId(),
			];
		}

		if (
			(!$timeFromEntry && $timeFromActivity)
			|| (
				$timeFromEntry
				&& $timeFromActivity
				&& $timeFromEntry->getTimestamp() <= $timeFromActivity->getTimestamp()
			)
		)
		{
			return [
				$timeFromActivity,
				ActivityController::resolveAuthorID($lastActivity->collectValues()),
			];
		}

		throw new InvalidOperationException('Unknown case');
	}

	private function getLastRelevantTimelineEntry(ItemIdentifier $timelineOwner): ?EO_Timeline
	{
		return $this->timelineDataManager::query()
			->setSelect([
				'AUTHOR_ID',
				'CREATED',
			])
			->where('BINDINGS.ENTITY_TYPE_ID', $timelineOwner->getEntityTypeId())
			->where('BINDINGS.ENTITY_ID', $timelineOwner->getEntityId())
			->where(
				(new ConditionTree())
					->logic(ConditionTree::LOGIC_OR)
					->where($this->getRelevantCommentFilter($timelineOwner))
					->where($this->getRelevantPingFilter())
			)
			->setOrder([
				'CREATED' => 'DESC',
			])
			->setLimit(1)
			->fetchObject()
		;
	}

	private function getRelevantCommentFilter(ItemIdentifier $timelineOwner): ConditionTree
	{
		$item = $this->getTimelineOwner($timelineOwner);

		$assigned = (int)$item?->getAssignedById();

		return (new ConditionTree())
			->whereNot('AUTHOR_ID', $assigned)
			->where('TYPE_ID', \Bitrix\Crm\Timeline\TimelineType::COMMENT)
		;
	}

	private function getRelevantPingFilter(): ConditionTree
	{
		return (new ConditionTree())
			->where('TYPE_ID', \Bitrix\Crm\Timeline\TimelineType::LOG_MESSAGE)
			->where('TYPE_CATEGORY_ID', \Bitrix\Crm\Timeline\LogMessageType::PING)
			->whereNot('ASSOCIATED_ENTITY_TYPE_ID', \CCrmOwnerType::SuspendedActivity)
		;
	}

	private function getLastRelevantActivity(ItemIdentifier $timelineOwner): ?EO_Activity
	{
		$lastIncomingActivity = $this->incomingChannelDataManager::query()
			->setSelect(['ACTIVITY_ID'])
			->where('BINDINGS.OWNER_TYPE_ID', $timelineOwner->getEntityTypeId())
			->where('BINDINGS.OWNER_ID', $timelineOwner->getEntityId())
			->setOrder([
				'ID' => 'DESC',
			])
			->setLimit(1)
			->fetchObject()
		;

		if (!$lastIncomingActivity)
		{
			return null;
		}

		return $this->activityDataManager::query()
			->setSelect([
				'CREATED',
				'EDITOR_ID',
				'AUTHOR_ID',
				'RESPONSIBLE_ID',
				'PROVIDER_ID',
			])
			->where('ID', $lastIncomingActivity->requireActivityId())
			->setLimit(1)
			->fetchObject()
		;
	}

	private function isActivitySuitable(int $activityId): bool
	{
		return \Bitrix\Crm\Activity\IncomingChannel::getInstance()->isIncomingChannel($activityId);
	}

	/**
	 * Last activity calculation over the timeline table is very expensive.
	 * Don't do it unless something relevant was added.
	 */
	private function isTimelineEntrySuitable(ItemIdentifier $timelineOwner, int $timelineEntryId): bool
	{
		$assignedById = (int)$this->getTimelineOwner($timelineOwner)?->getAssignedById();
		$cacheKey = $assignedById . ':' . $timelineEntryId;

		if (array_key_exists($cacheKey, $this->suitableTimelineEntriesCache))
		{
			return $this->suitableTimelineEntriesCache[$cacheKey];
		}

		$timelineEntry = $this->timelineDataManager::query()
			->setSelect([
				'AUTHOR_ID',
				'TYPE_ID',
				'TYPE_CATEGORY_ID',
				'ASSOCIATED_ENTITY_TYPE_ID',
			])
			->where('ID', $timelineEntryId)
			->fetchObject()
		;

		if (!$timelineEntry)
		{
			$this->suitableTimelineEntriesCache[$cacheKey] = false;

			return false;
		}

		$this->suitableTimelineEntriesCache[$cacheKey] =
			$this->isRelevantComment($timelineEntry, $assignedById)
			|| $this->isRelevantPing($timelineEntry)
		;

		return $this->suitableTimelineEntriesCache[$cacheKey];
	}

	private function isRelevantComment(EO_Timeline $timelineEntry, int $assignedById): bool
	{
		return (
			$timelineEntry->getAuthorId() !== $assignedById
			&& $timelineEntry->getTypeId() === \Bitrix\Crm\Timeline\TimelineType::COMMENT
		);
	}

	private function isRelevantPing(EO_Timeline $timelineEntry): bool
	{
		return (
			$timelineEntry->getTypeId() === \Bitrix\Crm\Timeline\TimelineType::LOG_MESSAGE
			&& $timelineEntry->getTypeCategoryId() === \Bitrix\Crm\Timeline\LogMessageType::PING
			&& $timelineEntry->getAssociatedEntityId() !== \CCrmOwnerType::SuspendedActivity
		);
	}
}
