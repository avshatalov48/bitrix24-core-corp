<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Kanban\Entity;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Pull\Event;
use Bitrix\Pull\Model\WatchTable;

class PullManager
{
	public const
		MODULE_ID = 'crm',
		EVENT_KANBAN_UPDATED = 'CRM_KANBANUPDATED';

	protected const
		EVENT_STAGE_ADDED = 'STAGEADDED',
		EVENT_STAGE_UPDATED = 'STAGEUPDATED',
		EVENT_STAGE_DELETED = 'STAGEDELETED';

	protected const
		EVENT_ITEM_ADDED = 'ITEMADDED',
		EVENT_ITEM_UPDATED = 'ITEMUPDATED',
		EVENT_ITEM_DELETED = 'ITEMDELETED';

	protected
		$eventIds = [],
		$isEnabled = false;

	private static $instance;

	public static function getInstance(): PullManager
	{
		if (!isset(self::$instance))
		{
			self::$instance = ServiceLocator::getInstance()->get('crm.integration.pullmanager');
		}

		return self::$instance;
	}

	public function __construct()
	{
		$this->isEnabled = $this->includeModule();
	}

	private function __clone()
	{
	}

	/**
	 * @return bool
	 */
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

	/**
	 * @return bool
	 */
	public function isEnabled(): bool
	{
		return $this->isEnabled;
	}

	/**
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	public function sendItemAddedEvent(array $item, ?array $params = null): bool
	{
		return $this->sendItemEvent(self::EVENT_ITEM_ADDED, $item, $params);
	}

	/**
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	public function sendItemUpdatedEvent(array $item, ?array $params = null): bool
	{
		return $this->sendItemEvent(self::EVENT_ITEM_UPDATED, $item, $params);
	}

	/**
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	public function sendItemDeletedEvent(array $item, ?array $params = null): bool
	{
		return $this->sendItemEvent(self::EVENT_ITEM_DELETED, $item, $params);
	}

	/**
	 * @param string $eventName
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	protected function sendItemEvent(string $eventName, array $item, ?array $params = null): bool
	{
		$kanbanTag = $this->getKanbanTag($params['TYPE'], $params);
		$eventParams = $this->prepareItemEventParams($item, $eventName);
		$eventParams['skipCurrentUser'] = (!isset($params['SKIP_CURRENT_USER']) || $params['SKIP_CURRENT_USER'] === true);

		$isKanbanEventSent = $this->sendKanbanEvent($item, $kanbanTag, $eventParams);

		$itemEventName = static::getItemEventName(static::EVENT_ITEM_UPDATED, (string)$params['TYPE'], (int)$item['id']);
		if ($itemEventName)
		{
			return $this->sendEvent($itemEventName, $eventParams);
		}

		return $isKanbanEventSent;
	}

	/**
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	public function sendStageAddedEvent(array $item, ?array $params = null): bool
	{
		return $this->sendStageEvent(self::EVENT_STAGE_ADDED, $item, $params);
	}

	/**
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	public function sendStageUpdatedEvent(array $item, ?array $params = null): bool
	{
		return $this->sendStageEvent(self::EVENT_STAGE_UPDATED, $item, $params);
	}

	/**
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	public function sendStageDeletedEvent(array $item, ?array $params = null): bool
	{
		return $this->sendStageEvent(self::EVENT_STAGE_DELETED, $item, $params);
	}

	/**
	 * @param string $eventName
	 * @param array $item
	 * @param array $params
	 * @return bool
	 */
	protected function sendStageEvent(string $eventName, array $item, $params = []): bool
	{
		$tag = $this->getKanbanTag($params['TYPE'], $params);
		$eventParams = $this->prepareStageEventParams($item, self::EVENT_STAGE_UPDATED);
		return $this->sendEvent($tag, $eventParams);
	}

	/**
	 * @param string $entityType
	 * @param array|null $params
	 * @return string
	 */
	protected function getKanbanTag(string $entityType, ?array $params = null): string
	{
		if(isset($params['CATEGORY_ID']))
		{
			$entityType .= '_' . (int)$params['CATEGORY_ID'];
		}
		return static::getEventName(static::EVENT_KANBAN_UPDATED, $entityType);
	}

	/**
	 * @param string $eventName
	 * @param string $item
	 * @return string
	 */
	protected static function getEventName(string $eventName, $item = ''): string
	{
		if(!empty($item) && (is_string($item) || is_numeric($item)))
		{
			$eventName .= '_' . $item;
		}

		return $eventName;
	}

	protected static function getItemEventName(string $eventName, string $entityType, int $itemId): ?string
	{
		if (!empty($entityType) && $itemId > 0)
		{
			return $eventName . '_' . $entityType . '_' . $itemId;
		}

		return null;
	}

	/**
	 * @param $item
	 * @param string $eventId
	 * @param array $params
	 * @return bool
	 */
	protected function sendKanbanEvent($item, string $eventId, array $params = []): bool
	{
		$params['eventId'] = $eventId;
		$userIds = $this->getSubscribedUserIdsWithItemPermissions($item, $eventId);

		if ($params['skipCurrentUser'])
		{
			$currentUser = CurrentUser::get()->getId();
			unset($userIds[$currentUser]);
		}
		unset($params['skipCurrentUser']);

		return $this->sendEvent($eventId, $params, $userIds);
	}

	/**
	 * @param $item
	 * @param string $eventName
	 * @return array
	 */
	protected function prepareItemEventParams($item, string $eventName = ''): array
	{
		return [
			'eventName' => $eventName,
			'item' => $item
		];
	}

	/**
	 * @param $stage
	 * @param string $eventName
	 * @return array
	 */
	protected function prepareStageEventParams($stage, string $eventName = ''): array
	{
		return [
			'eventName' => $eventName,
			'stage' => $stage
		];
	}

	/**
	 * @param $item
	 * @param string $eventName
	 * @return array
	 */
	protected function getSubscribedUserIdsWithItemPermissions($item, string $eventName): array
	{
		if(!$this->isEnabled())
		{
			return [];
		}
		$userIds = WatchTable::getUserIdsByTag($eventName);
		return $this->filterUserIdsWhoCanViewItem($item, $userIds, $eventName);
	}

	/**
	 * @param array $item
	 * @param array $userIds
	 * @param string $eventName
	 * @return array
	 */
	protected function filterUserIdsWhoCanViewItem(array $item, array $userIds, string $eventName): array
	{
		$typeWithCategoryId = explode(
			'_',
			str_replace(self::EVENT_KANBAN_UPDATED . '_', '', $eventName)
		);
		$type = (
			$typeWithCategoryId[0] === 'DYNAMIC'
				? $typeWithCategoryId[0] . '_' . $typeWithCategoryId[1]
				: $typeWithCategoryId[0]
		);

		$result = [];
		if($entity = Entity::getInstance($type))
		{
			foreach($userIds as $userId)
			{
				$userId = (int)$userId;
				if($userId > 0)
				{
					$permissions = \CCrmPerms::GetUserPermissions($userId);
					if($entity->checkReadPermissions($item['id'], $permissions))
					{
						$result[$userId] = $userId;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $entityType
	 * @param array|null $params
	 * @return string|null
	 */
	public function subscribeOnKanbanUpdate(string $entityType, ?array $params = null): ?string
	{
		return $this->subscribeOnEvent($this->getKanbanTag($entityType, $params));
	}

	public function subscribeOnItemUpdate(int $entityTypeId, int $itemId): ?string
	{
		$eventName = static::getItemEventName(
			static::EVENT_ITEM_UPDATED,
			\CCrmOwnerType::ResolveName($entityTypeId),
			$itemId
		);

		if ($eventName)
		{
			return $this->subscribeOnEvent($eventName);
		}

		return null;
	}

	protected function subscribeOnEvent(string $tag, bool $immediate = true): ?string
	{
		if($this->isEnabled && !empty($tag))
		{
			$addResult = \CPullWatch::Add(\CCrmPerms::getCurrentUserID(), $tag, $immediate);
			if($addResult)
			{
				return $tag;
			}
		}

		return null;
	}

	protected function sendEvent(string $tag, array $params = [], array $userIds = null): bool
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
					'module_id' => static::MODULE_ID,
					'command' => $tag,
					'params' => $params,
				]);
			}
		}
		else
		{
			return \CPullWatch::AddToStack($tag, [
				'module_id' => static::MODULE_ID,
				'command' => $tag,
				'params' => $params,
			]);
		}

		return false;
	}

	public static function onGetDependentModule(): array
	{
		return [
			'MODULE_ID' => static::MODULE_ID,
			'USE' => ['PUBLIC_SECTION'],
		];
	}
}