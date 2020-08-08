<?php

namespace Bitrix\Rpa\Integration;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Pull\Event;
use Bitrix\Pull\Model\WatchTable;
use Bitrix\Rpa\Driver;
use Bitrix\Rpa\Model\Item;
use Bitrix\Rpa\Model\Stage;
use Bitrix\Rpa\Model\Timeline;
use Bitrix\Rpa\Model\Type;
use Bitrix\Rpa\UserPermissions;

class PullManager
{
	protected const EVENT_KANBAN_UPDATED = 'KANBANUPDATED';
	protected const EVENT_TYPE_UPDATED = 'TYPEUPDATED';
	protected const EVENT_STAGE_UPDATED = 'STAGEUPDATED';
	protected const EVENT_ITEM_UPDATED = 'ITEMUPDATED';
	protected const EVENT_STAGE_DELETED = 'STAGEDELETED';
	protected const EVENT_ITEM_DELETED = 'ITEMDELETED';
	protected const EVENT_STAGE_ADDED = 'STAGEADDED';
	protected const EVENT_ITEM_ADDED = 'ITEMADDED';
	protected const EVENT_ROBOT_ADDED = 'ROBOTADDED';
	protected const EVENT_ROBOT_UPDATED = 'ROBOTUPDATED';
	protected const EVENT_ROBOT_DELETED = 'ROBOTDELETED';
	protected const EVENT_TIMELINE = 'TIMELINEUPDATED';
	protected const EVENT_TASK_COUNTERS = 'TASKCOUNTERS';

	protected $eventIds = [];
	protected $isEnabled;

	public function __construct()
	{
		$this->isEnabled = $this->includeModule();
	}

	protected function includeModule(): bool
	{
		try
		{
			return Loader::includeModule('pull');
		}
		catch(LoaderException $exception)
		{
			return false;
		}
	}

	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	//region subscribe
	public function subscribeOnTimelineUpdate(int $typeId, int $itemId): ?string
	{
		return $this->subscribeOnEvent(static::getEventName(static::EVENT_TIMELINE, $typeId, $itemId));
	}

	public function subscribeOnKanbanUpdate(int $typeId): ?string
	{
		return $this->subscribeOnEvent($this->getKanbanTag($typeId));
	}

	public function subscribeOnItemUpdatedEvent(int $typeId, int $itemId): ?string
	{
		return $this->subscribeOnEvent(static::getEventName(static::EVENT_ITEM_UPDATED, $typeId, $itemId));
	}

	public function subscribeOnTaskCounters(): ?string
	{
		return $this->subscribeOnEvent(static::EVENT_TASK_COUNTERS);
	}
	//endregion

	//region send events
	public function sendTimelineAddEvent(Timeline $timeline, string $eventId = ''): bool
	{
		$typeId = $timeline->getTypeId();
		$itemId = $timeline->getItemId();
		if($typeId > 0 && $itemId > 0)
		{
			$eventName = static::getEventName(static::EVENT_TIMELINE, $typeId, $itemId);
			$eventParams = $this->prepareTimelineEventParams($timeline, 'add', $eventId);

			return $this->sendEvent($eventName, $eventParams);
		}

		return false;
	}

	public function sendTimelineUpdateEvent(Timeline $timeline, string $eventId = ''): bool
	{
		$typeId = $timeline->getTypeId();
		$itemId = $timeline->getItemId();
		if($typeId > 0 && $itemId > 0)
		{
			$eventName = static::getEventName(static::EVENT_TIMELINE, $typeId, $itemId);
			$eventParams = $this->prepareTimelineEventParams($timeline, 'update', $eventId);

			return $this->sendEvent($eventName, $eventParams);
		}

		return false;
	}

	public function sendTimelinePinEvent(Timeline $timeline, string $eventId = ''): bool
	{
		$typeId = $timeline->getTypeId();
		$itemId = $timeline->getItemId();
		if($typeId > 0 && $itemId > 0)
		{
			$eventName = static::getEventName(static::EVENT_TIMELINE, $typeId, $itemId);
			$eventParams = $this->prepareTimelineEventParams($timeline, 'pin', $eventId);

			return $this->sendEvent($eventName, $eventParams);
		}

		return false;
	}

	public function sendTimelineDeleteEvent(int $typeId, int $itemId, int $timelineId, string $eventId = ''): bool
	{
		if($typeId > 0 && $itemId > 0)
		{
			$eventName = static::getEventName(static::EVENT_TIMELINE, $typeId, $itemId);

			return $this->sendEvent($eventName, [
				'command' => 'delete',
				'timeline' => [
					'id' => $timelineId,
				],
				'eventId' => $eventId,
			]);
		}

		return false;
	}

	public function sendItemAddedEvent(Item $item, string $eventId = ''): bool
	{
		$eventName = static::getEventName(static::EVENT_ITEM_ADDED, $item->getType()->getId());
		$eventParams = $this->prepareItemEventParams($item, $eventId);
		$this->sendKanbanEvent($item, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendItemUpdatedEvent(Item $item, string $eventId = '', Item $historyItem = null): bool
	{
		$eventName = static::getEventName(static::EVENT_ITEM_UPDATED, $item->getType()->getId(), $item->getId());
		if(empty($eventId))
		{
			$eventId = $this->getItemUpdateEventId($item->getType()->getId(), $item->getId());
		}
		$eventParams = $this->prepareItemEventParams($item, $eventId, $historyItem);
		$this->sendKanbanEvent($item, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendItemDeletedEvent(Item $item, string $eventId = ''): bool
	{
		$eventName = static::getEventName(static::EVENT_ITEM_DELETED, $item->getType()->getId(), $item->getId());
		$eventParams = $this->prepareItemEventParams($item, $eventId);
		$this->sendKanbanEvent($item, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendRobotAddedEvent(int $typeId, int $stageId, array $data = []): bool
	{
		$eventName = static::getEventName(static::EVENT_ROBOT_ADDED, $typeId);
		$eventParams = $this->prepareRobotEventParams($stageId, $data);
		$this->sendKanbanEvent($typeId, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendRobotUpdatedEvent(int $typeId, int $stageId, array $data = []): bool
	{
		if(empty($data['robotName']) || !is_string($data['robotName']))
		{
			return false;
		}

		$eventName = static::getEventName(static::EVENT_ROBOT_UPDATED, $typeId, $data['robotName']);
		$eventParams = $this->prepareRobotEventParams($stageId, $data);
		$this->sendKanbanEvent($typeId, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendRobotDeletedEvent(int $typeId, int $stageId, string $robotName, string $eventId = ''): bool
	{
		$eventName = static::getEventName(static::EVENT_ROBOT_DELETED, $typeId, $robotName);
		$eventParams = $this->prepareRobotEventParams($stageId, [
			'robotName' => $robotName,
		], $eventId);
		$this->sendKanbanEvent($typeId, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendTypeUpdatedEvent(Type $type, string $eventId = ''): bool
	{
		$eventName = static::getEventName(static::EVENT_TYPE_UPDATED, $type->getId());
		$eventParams = $this->prepareTypeEventParams($type, $eventId);
		$this->sendKanbanEvent($type, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendStageAddedEvent(Stage $stage, string $eventId = ''): bool
	{
		$eventName = static::getEventName(static::EVENT_STAGE_ADDED, $stage->getTypeId());
		$eventParams = $this->prepareStageEventParams($stage, $eventId);
		$this->sendKanbanEvent($stage, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendStageUpdatedEvent(Stage $stage, string $eventId = ''): bool
	{
		$eventName = static::getEventName(static::EVENT_STAGE_UPDATED, $stage->getTypeId(), $stage->getId());
		$eventParams = $this->prepareStageEventParams($stage, $eventId);
		$this->sendKanbanEvent($stage, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendStageDeletedEvent(int $stageId, int $typeId, string $eventId = ''): bool
	{
		$eventName = static::getEventName(static::EVENT_STAGE_DELETED, $typeId, $stageId);
		$eventParams = [
			'eventId' => $eventId,
			'stage' => [
				'id' => $stageId,
			]
		];
		$this->sendKanbanEvent($typeId, $eventName, $eventParams);

		return $this->sendEvent($eventName, $eventParams);
	}

	public function sendTaskCountersEvent(int $typeId, int $itemId, array $counters): bool
	{
		if(!$this->isEnabled())
		{
			return false;
		}

		$eventId = $this->getItemUpdateEventId($typeId, $itemId);
		$eventParams = [
			'itemId' => $itemId,
			'typeId' => $typeId,
			'eventId' => $eventId,
		];
		$taskManager = Driver::getInstance()->getTaskManager();
		if($taskManager)
		{
			$eventParams['tasksFaces'] = $taskManager->getItemFaces($typeId, $itemId);
		}
		$eventName = static::EVENT_TASK_COUNTERS;
		foreach($counters as $userId => $counter)
		{
			$eventParams['counter'] = $counter;
			$this->sendEvent($eventName, $eventParams, [$userId]);
		}

		return true;
	}

	protected function sendKanbanEvent($object, string $eventName, array $params = []): bool
	{
		$typeId = $this->getTypeIdByModel($object);
		if($typeId !== null)
		{
			$params['eventName'] = $eventName;
			$userIds = null;
			if($object instanceof Item)
			{
				$userIds = $this->getSubscribedUserIdsWithItemPermissions($object, $this->getKanbanTag($typeId));
			}
			return $this->sendEvent($this->getKanbanTag($typeId), $params, $userIds);
		}

		return false;
	}
	//endregion

	protected function prepareItemEventParams(Item $item, string $eventId = '', Item $historyItem = null): array
	{
		$controller = new \Bitrix\Rpa\Controller\Item();
		$data = $controller->prepareItemData($item, [
			'withDisplay' => true,
			'withTasks' => true,
			'withUsers' => true,
			'withPermissions' => true,
		]);
		return [
			'eventId' => $eventId,
			'item' => $data,
			'itemChangedUserFieldNames' => $historyItem ? $historyItem->getChangedUserFieldNames() : null
		];
	}

	protected function prepareTypeEventParams(Type $type, string $eventId): array
	{
		$controller = new \Bitrix\Rpa\Controller\Type();
		$data = $controller->prepareData($type);
		return [
			'eventId' => $eventId,
			'type' => $data,
		];
	}

	protected function getSubscribedUserIdsWithItemPermissions(Item $item, string $eventName): array
	{
		if(!$this->isEnabled())
		{
			return [];
		}
		$userIds = WatchTable::getUserIdsByTag($eventName);
		return UserPermissions::filterUserIdsWhoCanViewItem($item, $userIds);
	}

	protected function getSubscribedUserIdsWithTypePermissions(int $typeId, string $eventName): array
	{
		if(!$this->isEnabled())
		{
			return [];
		}
		$userIds = WatchTable::getUserIdsByTag($eventName);
		return UserPermissions::filterUserIdsWhoCanViewType($typeId, $userIds);
	}

	protected function getKanbanTag(int $typeId): string
	{
		return static::getEventName(static::EVENT_KANBAN_UPDATED, $typeId);
	}

	protected function prepareStageEventParams(Stage $stage, $eventId = ''): array
	{
		$controller = new \Bitrix\Rpa\Controller\Stage();
		$data = $controller->prepareData($stage);
		return [
			'eventId' => $eventId,
			'stage' => $data,
		];
	}

	protected function getTypeIdByModel($object): ?int
	{
		if(is_numeric($object))
		{
			return (int)$object;
		}

		if($object instanceof Item)
		{
			return $object->getType()->getId();
		}

		if($object instanceof Stage)
		{
			return $object->getType()->getId();
		}

		if($object instanceof Type)
		{
			return $object->getId();
		}

		return null;
	}

	protected function subscribeOnEvent(string $eventName, bool $immediate = true): ?string
	{
		if($this->isEnabled && !empty($eventName))
		{
			$addResult = \CPullWatch::Add(Driver::getInstance()->getUserId(), $eventName, $immediate);
			if($addResult)
			{
				return $eventName;
			}
		}

		return null;
	}

	protected function sendEvent(string $eventName, array $params = [], array $userIds = null): bool
	{
		if(!$this->isEnabled())
		{
			return false;
		}
		if(is_array($userIds))
		{
			if(!empty($userIds))
			{
				return Event::add($userIds, [
					'module_id' => Driver::MODULE_ID,
					'command' => $eventName,
					'params' => $params,
				]);
			}
		}
		else
		{
			return \CPullWatch::AddToStack($eventName, [
				'module_id' => Driver::MODULE_ID,
				'command' => $eventName,
				'params' => $params,
			]);
		}

		return false;
	}

	protected function prepareRobotEventParams(int $stageId, array $data = [], string $eventId = ''): array
	{
		return [
			'robot' => array_merge($data, ['stageId' => $stageId]),
			'eventId' => $eventId,
		];
	}

	protected static function getEventName(string $eventName, int $typeId, $item = ''): string
	{
		$eventName .= $typeId;
		if(!empty($item) && (is_string($item) || is_numeric($item)))
		{
			$eventName .= '_'.$item;
		}

		return $eventName;
	}

	protected function prepareTimelineEventParams(Timeline $timeline, string $command, string $eventId = ''): array
	{
		return [
			'command' => $command,
			'timeline' => $timeline->preparePublicData(),
			'eventId' => $eventId,
		];
	}

	public function addItemUpdateEventId(int $typeId, int $itemId, string $eventId): PullManager
	{
		$this->eventIds['item_update'][$typeId][$itemId] = $eventId;

		return $this;
	}

	protected function getItemUpdateEventId(int $typeId, int $itemId): string
	{
		return $this->eventIds['item_update'][$typeId][$itemId] ?? '';
	}
}