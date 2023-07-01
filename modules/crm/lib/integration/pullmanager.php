<?php

namespace Bitrix\Crm\Integration;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Pull\Event;
use Bitrix\Pull\Model\WatchTable;

class PullManager
{
	public const MODULE_ID = 'crm';
	public const EVENT_KANBAN_UPDATED = 'CRM_KANBANUPDATED';

	protected const EVENT_STAGE_ADDED = 'STAGEADDED';
	protected const EVENT_STAGE_UPDATED = 'STAGEUPDATED';
	protected const EVENT_STAGE_DELETED = 'STAGEDELETED';

	protected const EVENT_ITEM_ADDED = 'ITEMADDED';
	protected const EVENT_ITEM_UPDATED = 'ITEMUPDATED';
	protected const EVENT_ITEM_DELETED = 'ITEMDELETED';

	protected $eventIds = [];
	protected $isEnabled = false;

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

	public function setEnabled(bool $enabled): self
	{
		$this->isEnabled = $enabled && $this->includeModule();

		return $this;
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
	 * In some cases may not contain additional payload about `action`.
	 * @param array $item
	 * @param array|null $params
	 * @return bool
	 */
	public function sendItemUpdatedEvent(array $item, ?array $params = null): bool
	{
		$this->sendItemEvent(self::EVENT_ITEM_UPDATED, $item, $params);

		return true;
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
		$eventParams = $this->prepareItemEventParams($item, $eventName, $params);

		$isKanbanEventSent = $this->sendKanbanEvent($item, $kanbanTag, $eventParams);

		$itemEventName = static::getItemEventName(static::EVENT_ITEM_UPDATED, (string)$params['TYPE'], (int)$item['id']);
		if ($itemEventName)
		{
			return $this->sendUserEvent($itemEventName, $eventParams);
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

		return $this->sendUserEvent($tag, $eventParams);
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
	 * @param string $eventName
	 * @param array $params
	 * @return bool
	 */
	protected function sendKanbanEvent($item, string $eventName, array $params = []): bool
	{
		$userIds = $this->getSubscribedUserIdsWithItemPermissions($item, $eventName);

		if ($params['skipCurrentUser'])
		{
			$currentUser = CurrentUser::get()->getId();
			unset($userIds[$currentUser]);
		}
		unset($params['skipCurrentUser']);

		return $this->sendUserEvent($eventName, $params, $userIds);
	}

	/**
	 * @param $item
	 * @param string $eventName
	 * @param array $params
	 * @return array
	 */
	protected function prepareItemEventParams($item, string $eventName = '', array $params = []): array
	{
		return [
			'eventName' => $eventName,
			'item' => $item,
			'skipCurrentUser' => (!isset($params['SKIP_CURRENT_USER']) || $params['SKIP_CURRENT_USER'] === true),
			'eventId' => ($params['EVENT_ID'] ?? null),
			'ignoreDelay' => ($params['IGNORE_DELAY'] ?? false),
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
		$withoutPrefix = str_replace(self::EVENT_KANBAN_UPDATED . '_', '', $eventName);
		$nameParts = (array)explode('_', $withoutPrefix);

		if ($nameParts[0] === 'DYNAMIC' || $nameParts[0] === 'SMART')
		{
			// like 'DYNAMIC_128' or 'SMART_INVOICE'
			$typeName = $nameParts[0] . '_' . $nameParts[1];
		}
		else
		{
			$typeName = $nameParts[0];
		}

		$result = [];

		$entityTypeId = \CCrmOwnerType::ResolveID($typeName);
		if (\CCrmOwnerType::IsEntity($entityTypeId))
		{
			foreach($userIds as $userId)
			{
				$userId = (int)$userId;
				if($userId > 0)
				{
					if (Container::getInstance()->getUserPermissions($userId)->checkReadPermissions(
						$entityTypeId,
						$item['id'],
						$item['data']['categoryId'] ?? null
					))
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

	public function sendCrmInitiatedEvent(?array $channelShared = null): bool
	{
		if(!$this->isEnabled())
		{
			return false;
		}

		if (!is_array($channelShared))
		{
			$channelShared = \CPullChannel::GetChannelShared();
		}

		if (!$channelShared)
		{
			return false;
		}

		return $this->sendChannelEvent(
			$channelShared['CHANNEL_ID'],
			'was_inited',
			[
				'expiry' => 180,
			]
		);
	}

	/**
	 * @return array|false
	 */
	public function getChannelShared()
	{
		return \CPullChannel::GetChannelShared();
	}

	public static function isPullChannelActiveByTag(string $tag): bool
	{
		if (Loader::includeModule('pull'))
		{
			$query = \Bitrix\Pull\Model\WatchTable::query();
			$query
				->addSelect('ID')
				->where('TAG', $tag)
				->setLimit(1)
			;

			return !!$query->fetch();
		}

		return false;
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

	protected function sendUserEvent(string $tag, array $params = [], array $userIds = null): bool
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

	protected function sendChannelEvent(string $channelId, string $tag, array $params = []): bool
	{
		if(!$this->isEnabled())
		{
			return false;
		}

		return \CPullStack::AddByChannel($channelId, [
			'module_id' => static::MODULE_ID,
			'command' => $tag,
			'expiry' => $params['expiry'] ?? 86400,
		]);
	}

	public static function onGetDependentModule(): array
	{
		return [
			'MODULE_ID' => static::MODULE_ID,
			'USE' => ['PUBLIC_SECTION'],
		];
	}
}
