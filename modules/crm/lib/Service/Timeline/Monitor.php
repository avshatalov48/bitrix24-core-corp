<?php

namespace Bitrix\Crm\Service\Timeline;

use Bitrix\Crm\Activity\Entity\IncomingChannelTable;
use Bitrix\Crm\ActivityTable;
use Bitrix\Crm\Integration\PullManager;
use Bitrix\Crm\Item;
use Bitrix\Crm\ItemIdentifier;
use Bitrix\Crm\Kanban\Entity;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Service\Context;
use Bitrix\Crm\Service\Factory;
use Bitrix\Crm\Timeline\ActivityController;
use Bitrix\Crm\Timeline\Entity\TimelineTable;
use Bitrix\Crm\Traits;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\Type\Collection;
use Bitrix\Main\Type\DateTime;

final class Monitor
{
	use Traits\Singleton;

	private static bool $isAgentRunning = false;

	/** @var Array<int, Array<int, Array<mixed, mixed>>> - [entityTypeId => [itemId => ['recalculate' => bool]]] */
	private array $changes = [];

	/** @var string|TimelineTable */
	private $timelineDataManager = TimelineTable::class;
	/** @var string|ActivityTable */
	private $activityDataManager = ActivityTable::class;
	/** @var string|IncomingChannelTable */
	private $incomingChannelDataManager = IncomingChannelTable::class;

	private $suitableActivitiesCache = [];
	private $suitableTimelineEntriesCache = [];

	private function __construct()
	{
		if (!self::$isAgentRunning)
		{
			Application::getInstance()->addBackgroundJob(fn() => $this->processChanges());
		}
	}

	public function isTimelineChanged(ItemIdentifier $timelineOwner): bool
	{
		$change = $this->changes[$timelineOwner->getEntityTypeId()][$timelineOwner->getEntityId()] ?? null;

		return ($change && $change['recalculate']);
	}

	public function onTimelineEntryAdd(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner, true);
	}

	public function onTimelineEntryAddIfSuitable(ItemIdentifier $timelineOwner, int $timelineEntryId): void
	{
		if ($this->isTimelineEntrySuitable($timelineOwner, $timelineEntryId))
		{
			$this->onTimelineEntryAdd($timelineOwner);
		}
	}

	public function onTimelineEntryRemove(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner, true);
	}

	public function onTimelineEntryRemoveIfSuitable(ItemIdentifier $timelineOwner, int $timelineEntryId): void
	{
		if ($this->isTimelineEntrySuitable($timelineOwner, $timelineEntryId))
		{
			$this->onTimelineEntryRemove($timelineOwner);
		}
	}

	public function onActivityAdd(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner, true);
	}

	public function onActivityAddIfSuitable(ItemIdentifier $timelineOwner, int $activityId): void
	{
		if ($this->isActivitySuitable($activityId))
		{
			$this->onActivityAdd($timelineOwner);
		}
	}

	public function onActivityRemove(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner, true);
	}

	public function onActivityRemoveIfSuitable(ItemIdentifier $timelineOwner, int $activityId): void
	{
		if ($this->isActivitySuitable($activityId))
		{
			$this->onActivityRemove($timelineOwner);
		}
	}

	public function onUncompletedActivityChange(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner, false);
	}

	public function onBadgesSync(ItemIdentifier $timelineOwner): void
	{
		$this->upsertChange($timelineOwner, false);
	}

	/**
	 * @internal For internal system usage only. Is not covered by backwards compatibility.
	 * Will be deleted in future versions.
	 *
	 * @return void
	 */
	public static function onLastActivityRecalculationByAgent(): void
	{
		self::$isAgentRunning = true;
	}

	private function upsertChange(ItemIdentifier $timelineOwner, bool $recalculate): void
	{
		$change = $this->changes[$timelineOwner->getEntityTypeId()][$timelineOwner->getEntityId()] ?? [];

		if (!isset($change['recalculate']))
		{
			$change['recalculate'] = $recalculate;
		}
		elseif ($recalculate && !$change['recalculate'])
		{
			$change['recalculate'] = true;
		}

		$this->changes[$timelineOwner->getEntityTypeId()][$timelineOwner->getEntityId()] = $change;
	}

	public function calculateLastActivityInfo(ItemIdentifier $timelineOwner): array
	{
		$lastTimelineEntryQuery =
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
		;
		$this->addTimelineTypeFilter($timelineOwner, $lastTimelineEntryQuery);

		$lastTimelineEntry = $lastTimelineEntryQuery->fetchObject();

		$lastIncomingActivity = $this->incomingChannelDataManager::query()
			->where('BINDINGS.OWNER_TYPE_ID', $timelineOwner->getEntityTypeId())
			->where('BINDINGS.OWNER_ID', $timelineOwner->getEntityId())
			->setOrder([
				'ID' => 'DESC',
			])
			->setLimit(1)
			->setSelect(['ACTIVITY_ID'])
			->fetchObject()
		;

		$lastActivity = $lastIncomingActivity
			? $this->activityDataManager::query()
				->setSelect([
					'CREATED',
					'EDITOR_ID',
					'AUTHOR_ID',
					'RESPONSIBLE_ID',
					'PROVIDER_ID',
				])
				->where('ID', $lastIncomingActivity->getActivityId())
				->setLimit(1)
				->fetchObject()
			: null
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

	private function processChanges(): void
	{
		if (self::$isAgentRunning)
		{
			return;
		}

		$container = Container::getInstance();

		foreach ($this->changes as $entityTypeId => $changesOfItemsOfSameType)
		{
			if ($entityTypeId === \CCrmOwnerType::Order && Loader::includeModule('sale'))
			{
				//todo delete when orders support factory based approach
				$this->sendOrdersUpdatedPushes(array_keys($changesOfItemsOfSameType));
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
					'@' . Item::FIELD_NAME_ID => array_keys($changesOfItemsOfSameType),
				],
			]);

			foreach ($items as $singleItem)
			{
				$this->processSingleItemChange($factory, $singleItem);
			}
		}
	}

	private function processSingleItemChange(Factory $factory, Item $item): void
	{
		$isPushAlreadySent = false;

		$shouldRecalculate = $this->isTimelineChanged(ItemIdentifier::createByItem($item));
		if ($shouldRecalculate && $item->hasField(Item::FIELD_NAME_LAST_ACTIVITY_TIME))
		{
			/** @var DateTime|null $lastActivityTimePrevious */
			$lastActivityTimePrevious = $item->get(Item::FIELD_NAME_LAST_ACTIVITY_TIME);
			$this->launchUpdateOperation($factory, $item);
			/** @var DateTime|null $lastActivityTimeCurrent */
			$lastActivityTimeCurrent = $item->get(Item::FIELD_NAME_LAST_ACTIVITY_TIME);

			if (
				$lastActivityTimePrevious
				&& $lastActivityTimeCurrent
				&& $lastActivityTimePrevious->getTimestamp() !== $lastActivityTimeCurrent->getTimestamp()
			)
			{
				$isPushAlreadySent = true;
			}
		}

		if (!$isPushAlreadySent)
		{
			$this->sendItemUpdatedPush($item);
		}
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
					'IGNORE_DELAY' => true,
				],
			);
		}
	}

	private function sendOrdersUpdatedPushes(array $ids): void
	{
		Collection::normalizeArrayValuesByInt($ids);
		if (empty($ids))
		{
			return;
		}

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

	private function addTimelineTypeFilter(ItemIdentifier $timelineOwner, Query $lastTimelineQuery): void
	{
		$lastTimelineQuery->where(Query::filter()
			->logic('OR')
			->where(Query::filter()
				->whereNot('AUTHOR_ID', $this->getOwnerAssignedBy($timelineOwner))
				->where('TYPE_ID', \Bitrix\Crm\Timeline\TimelineType::COMMENT)
			)
			->where(Query::filter()
				->where('TYPE_ID', \Bitrix\Crm\Timeline\TimelineType::LOG_MESSAGE)
				->where('TYPE_CATEGORY_ID', \Bitrix\Crm\Timeline\LogMessageType::PING)
				->whereNot('ASSOCIATED_ENTITY_TYPE_ID', \CCrmOwnerType::SuspendedActivity)
			)
		);
	}

	private function isActivitySuitable(int $activityId): bool
	{
		if (!array_key_exists($activityId, $this->suitableActivitiesCache))
		{
			$this->suitableActivitiesCache[$activityId] = \Bitrix\Crm\Activity\IncomingChannel::getInstance()->isIncomingChannel($activityId);
		}

		return $this->suitableActivitiesCache[$activityId];
	}

	private function isTimelineEntrySuitable(ItemIdentifier $timelineOwner, int $timelineEntryId): bool
	{
		$assignedById = $this->getOwnerAssignedBy($timelineOwner);
		$cacheKey = $assignedById . ':' . $timelineEntryId;

		if (!array_key_exists($cacheKey, $this->suitableTimelineEntriesCache))
		{
			$timelineEntry = TimelineTable::query()
				->where('ID', $timelineEntryId)
				->setSelect([
					'AUTHOR_ID',
					'TYPE_ID',
					'TYPE_CATEGORY_ID',
					'ASSOCIATED_ENTITY_TYPE_ID',
				])
				->fetch()
			;
			if (!$timelineEntry)
			{
				$this->suitableTimelineEntriesCache[$cacheKey] = false;
			}
			else
			{
				$this->suitableTimelineEntriesCache[$cacheKey] = (
					(
						$timelineEntry['AUTHOR_ID'] != $assignedById
						&& $timelineEntry['TYPE_ID'] == \Bitrix\Crm\Timeline\TimelineType::COMMENT
					)
					||
					(
						$timelineEntry['TYPE_ID'] == \Bitrix\Crm\Timeline\TimelineType::LOG_MESSAGE
						&& $timelineEntry['TYPE_CATEGORY_ID'] == \Bitrix\Crm\Timeline\LogMessageType::PING
						&& $timelineEntry['ASSOCIATED_ENTITY_TYPE_ID'] != \CCrmOwnerType::SuspendedActivity
					)
				);
			}
		}

		return $this->suitableTimelineEntriesCache[$cacheKey];
	}

	private function getOwnerAssignedBy(ItemIdentifier $timelineOwner): int
	{
		$factory = Container::getInstance()->getFactory($timelineOwner->getEntityTypeId());
		if (!$factory)
		{
			return 0;
		}
		if (!$factory->isFieldExists(\Bitrix\Crm\Item::FIELD_NAME_ASSIGNED))
		{
			return 0;
		}

		$assignedByFieldName = $factory->getEntityFieldNameByMap(\Bitrix\Crm\Item::FIELD_NAME_ASSIGNED);

		return $factory->getDataClass()::getList([
				'filter' => [
					'=ID' => $timelineOwner->getEntityId(),
				],
				'select' => [
					$assignedByFieldName,
				],
				'limit' => 1,
			])->fetch()[$assignedByFieldName] ?? 0
		;
	}
}
