<?php
namespace Bitrix\Crm\Counter;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;

class EntityCounterType
{
	// ATTENTION!!!!
	// Don't forget to make changes to the JS files with same constants.
	// Like crm/install/components/bitrix/crm.entity.counter.panel/templates/.default/src/entity-counter-type.js
	// or use the project search, so you don't miss other places, if any.
	public const UNDEFINED = 0;
	public const IDLE  = 1;
	public const PENDING = 2;
	public const OVERDUE = 4;
	public const INCOMING_CHANNEL = 8;
	public const READY_TODO = 16;

	public const CURRENT = 20;  // READY_TODO|OVERDUE
	public const ALL_DEADLINE_BASED = 23;  //IDLE|PENDING|OVERDUE|READY_TODO
	public const ALL = 31;  //IDLE|PENDING|OVERDUE|INCOMINGCHANNEL|READY_TODO

	public const FIRST = 1;
	public const LAST = 31;

	public const IDLE_NAME  = 'IDLE';
	public const PENDING_NAME = 'PENDING';
	public const OVERDUE_NAME = 'OVERDUE';
	public const CURRENT_NAME = 'CURRENT';
	public const INCOMING_CHANNEL_NAME = 'INCOMINGCHANNEL';
	public const READY_TODO_NAME = 'READYTODO';
	public const ALL_DEADLINE_BASED_NAME = 'ALLDEADLINEBASED';
	public const ALL_NAME = 'ALL';

	public const EXCLUDE_USERS_CODE_SUFFIX = 'excl';

	private static $all = null;

	/**
	 * @param int $typeID Type ID.
	 * @return bool
	 */
	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		return
			$typeID === self::IDLE
			|| $typeID === self::PENDING
			|| $typeID === self::OVERDUE
			|| $typeID === self::CURRENT
			|| $typeID === self::INCOMING_CHANNEL
			|| $typeID === self::READY_TODO
			|| $typeID === self::ALL_DEADLINE_BASED
			|| $typeID === self::ALL
		;
	}
	public static function isGrouping($typeID)
	{
		return in_array($typeID, self::getGroupings());
	}

	/**
	 * Check if $possiblyGroupingTypeId is a grouping type in context of $allTypeIds
	 * @param int $possiblyGroupingTypeId
	 * @param array $allTypeIds
	 * @return bool
	 */
	public static function isGroupingForArray(int $possiblyGroupingTypeId, array $allTypeIds): bool
	{
		foreach ($allTypeIds as $typeId)
		{
			if ($possiblyGroupingTypeId !== $typeId && (($possiblyGroupingTypeId & $typeId) === $typeId))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if specified counter type is supported for specified entity type.
	 * @param int $typeID Entity Counter Type ID.
	 * @param int $entityTypeID Entity Type ID.
	 * @return bool
	 */
	public static function isSupported($typeID, $entityTypeID)
	{
		$typeID = (int)$typeID;
		$entityTypeID = (int)$entityTypeID;
		$factory = Container::getInstance()->getFactory($entityTypeID);

		return ($factory && $factory->getCountersSettings()->isCounterTypeEnabled($typeID));
	}
	/**
	 * @param int $typeID Type ID.
	 * @return string
	 */
	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = (int)$typeID;

		if($typeID === self::IDLE)
		{
			return self::IDLE_NAME;
		}
		elseif($typeID === self::PENDING)
		{
			return self::PENDING_NAME;
		}
		elseif($typeID === self::OVERDUE)
		{
			return self::OVERDUE_NAME;
		}
		elseif($typeID === self::CURRENT)
		{
			return self::CURRENT_NAME;
		}
		elseif($typeID === self::INCOMING_CHANNEL)
		{
			return self::INCOMING_CHANNEL_NAME;
		}
		elseif($typeID === self::ALL_DEADLINE_BASED)
		{
			return self::ALL_DEADLINE_BASED_NAME;
		}
		elseif($typeID === self::ALL)
		{
			return self::ALL_NAME;
		}
		elseif($typeID === self::READY_TODO)
		{
			return self::READY_TODO_NAME;
		}
		return '';
	}
	/**
	 * @param string $typeName Type Name.
	 * @return int
	 */
	public static function resolveID($typeName)
	{
		if(!is_string($typeName))
		{
			return self::UNDEFINED;
		}

		$typeName = mb_strtoupper($typeName);
		if($typeName === self::IDLE_NAME)
		{
			return self::IDLE;
		}
		elseif($typeName === self::PENDING_NAME)
		{
			return self::PENDING;
		}
		elseif($typeName === self::OVERDUE_NAME)
		{
			return self::OVERDUE;
		}
		elseif($typeName === self::INCOMING_CHANNEL_NAME)
		{
			return self::INCOMING_CHANNEL;
		}
		elseif($typeName === self::CURRENT_NAME)
		{
			return self::CURRENT;
		}
		elseif($typeName === self::ALL_DEADLINE_BASED_NAME)
		{
			return self::ALL_DEADLINE_BASED;
		}
		elseif($typeName === self::ALL_NAME)
		{
			return self::ALL;
		}
		return self::UNDEFINED;
	}
	/**
	 * @return array
	 */
	public static function getAll($enableGrouping = false)
	{
		$types = [self::IDLE, self::PENDING, self::OVERDUE, self::INCOMING_CHANNEL, self::READY_TODO];

		if(!$enableGrouping)
		{
			return $types;
		}
		return array_merge($types, self::getGroupings());
	}

	public static function getAllDeadlineBased(bool $enableGrouping = false): array
	{
		$result = [
			self::IDLE,
			self::PENDING,
			self::OVERDUE
		];

		if ($enableGrouping)
		{
			$result = array_merge($result, self::getGroupings());
		}

		return $result;
	}

	public static function getAllLightTimeBased(bool $enableGrouping = false): array
	{
		$result = [
			self::READY_TODO
		];

		if ($enableGrouping)
		{
			$result = array_merge($result, self::getGroupings());
		}

		return $result;
	}

	public static function getAllIncomingBased(bool $enableGrouping = false): array
	{
		$result = [
			self::INCOMING_CHANNEL,
		];

		if ($enableGrouping)
		{
			$result = array_merge($result, [
				self::ALL,
			]);
		}

		return $result;
	}

	/**
	 * @param $entityTypeID
	 * @param bool $enableGrouping
	 * @return array
	 */
	public static function getAllSupported($entityTypeID, $enableGrouping = false)
	{
		$entityTypeID = (int)$entityTypeID;
		$factory = Container::getInstance()->getFactory($entityTypeID);
		if ($factory)
		{
			$countersTypes = $factory->getCountersSettings()->getEnabledCountersTypes();
		}
		else
		{
			$counterSettings = EntityCounterSettings::createDefault(true);
			$countersTypes = $counterSettings->getEnabledCountersTypes();
		}

		if (!$enableGrouping)
		{
			$countersTypes = array_diff($countersTypes, self::getGroupings());
		}

		return $countersTypes;
	}

	public static function getGroupings()
	{
		return [
			self::CURRENT,
			self::ALL_DEADLINE_BASED,
			self::ALL,
		];
	}
	public static function joinType(array $typeIDs)
	{
		$result = 0;
		foreach($typeIDs as $typeID)
		{
			$result |= $typeID;
		}
		return $result;
	}
	public static function splitType($typeID)
	{
		if(!is_numeric($typeID))
		{
			return array();
		}

		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		$results = array();
		foreach(self::getAll() as $ID)
		{
			if(($typeID&$ID) === $ID)
			{
				$results[] = $ID;
			}
		}
		return $results;
	}

	public static function getListFilterInfo(array $params = null, array $options = null)
	{
		Main\Localization\Loc::loadMessages(__FILE__);

		if($params === null)
		{
			$params = [];
		}

		if($options === null)
		{
			$options = [];
		}
		$entityTypeId = (int)($options['ENTITY_TYPE_ID'] ?? \CCrmOwnerType::Undefined);

		$items = [];
		if(!(isset($params['params']) && isset($params['params']['multiple']) && strcasecmp($params['params']['multiple'], 'Y') === 0))
		{
			//Add 'Not Selected' for single filter
			$items[''] = '';
		}

		$entityTypeId = (int)$entityTypeId;
		$factory = Container::getInstance()->getFactory($entityTypeId);
		if (!$factory && $entityTypeId !== \CCrmOwnerType::Activity)
		{
			return [];
		}

		if ($factory)
		{
			$countersSettings = $factory->getCountersSettings();
		}
		else
		{
			$countersSettings = EntityCounterSettings::createDefault(true);
		}

		if ($countersSettings->isIncomingCounterEnabledInFilter())
		{
			$items[self::INCOMING_CHANNEL] = GetMessage('CRM_ENTITY_COUNTER_TYPE_FILTER_INCOMING_CHANNEL');
		}
		if ($countersSettings->isPendingCounterEnabledInFilter() || $countersSettings->isCurrentCounterEnabledInFilter())
		{
			$items[self::PENDING] = GetMessage('CRM_ENTITY_COUNTER_TYPE_FILTER_PENDING');
		}
		if ($countersSettings->isOverdueCounterEnabledInFilter() || $countersSettings->isCurrentCounterEnabledInFilter())
		{
			$items[self::OVERDUE] = GetMessage('CRM_ENTITY_COUNTER_TYPE_FILTER_OVERDUE');
		}
		if ($countersSettings->isIdleCounterEnabledInFilter())
		{
			$items[self::IDLE] = GetMessage('CRM_ENTITY_COUNTER_TYPE_FILTER_IDLE');
		}
		if ($countersSettings->isReadyToDoCounterEnabledInFilter())
		{
			$items[self::READY_TODO] = GetMessage('CRM_ENTITY_COUNTER_TYPE_FILTER_READY_TODO');
		}

		return array_merge(
			[
				'type' => 'list',
				'items' => $items,
			],
			$params
		);
	}
}
