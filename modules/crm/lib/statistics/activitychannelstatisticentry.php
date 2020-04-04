<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Query;
use Bitrix\Crm\Statistics\Entity\ActivityChannelStatisticsTable;

class ActivityChannelStatisticEntry extends StatisticEntryBase
{
	/** @var ActivityChannelStatisticEntry|null */
	private static $current = null;
	private static $messagesLoaded = false;
	/**
	 * Get all
	 * @param int $ownerID Owner ID.
	 * @return array
	 */
	public static function getAll($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(ActivityChannelStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addOrder('CREATED_DATE', 'ASC');

		$dbResult = $query->exec();
		$results = array();

		while($fields = $dbResult->fetch())
		{
			$results[] = $fields;
		}
		return $results;
	}
	/**
	 * Get record
	 * @param int $ownerID Owner ID.
	 * @param int $channelTypeID Channel Type ID.
	 * @return array
	 */
	public static function get($ownerID, $channelTypeID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}


		$query = new Query(ActivityChannelStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addFilter('=CHANNEL_TYPE_ID', $channelTypeID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}
	/**
	 * Check if entity is registered
	 * @param int $ownerID Owner ID.
	 * @param int $channelTypeID Channel Type ID.
	 * @return boolean
	 */
	public static function isRegistered($ownerID, $channelTypeID)
	{
		return is_array(self::get($ownerID, $channelTypeID));
	}
	/**
	 * Register Entity
	 * @param int $ownerID Owner ID.
	 * @param int $channelTypeID Channel Type ID.
	 * @param array $bindingParams Array of binding parameters. For example ORIGIN_ID and COMPONENT_ID.*
	 * @params array $entityFields Entity fields.
	 * @params array $options Options.
	 * @return void
	 */
	public static function register($ownerID, $channelTypeID, array $bindingParams = null, array $entityFields = null, array $options = null)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if($channelTypeID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'channelTypeID');
		}

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmActivity::GetList(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('RESPONSIBLE_ID', 'COMPLETED', 'CREATED', 'LAST_UPDATED', "DIRECTION")
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return;
			}
		}
		$completed = isset($entityFields['COMPLETED']) ? $entityFields['COMPLETED'] : 'N';
		$responsibleID = isset($entityFields['RESPONSIBLE_ID']) ? (int)$entityFields['RESPONSIBLE_ID'] : 0;
		$direction = isset($entityFields['DIRECTION']) ? $entityFields['DIRECTION'] : \CCrmActivityDirection::Outgoing;

		/** @var Date $date */
		$date = self::parseDateString(isset($entityFields['CREATED']) ? $entityFields['CREATED'] : '');
		if($date === null)
		{
			$date = new Date();
		}

		$latest = self::get($ownerID, $channelTypeID);
		if(is_array($latest)
			&& $responsibleID === (int)$latest['RESPONSIBLE_ID']
			&& $completed === $latest['COMPLETED']
		)
		{
			return;
		}


		$data = array(
			'OWNER_ID' => $ownerID,
			'CREATED_DATE' => $date,
			'CHANNEL_TYPE_ID' => $channelTypeID,
			'CHANNEL_ORIGIN_ID' => isset($bindingParams['ORIGIN_ID']) ? $bindingParams['ORIGIN_ID'] : '',
			'CHANNEL_COMPONENT_ID' => isset($bindingParams['COMPONENT_ID']) ? $bindingParams['COMPONENT_ID'] : '',
			'RESPONSIBLE_ID' => $responsibleID,
			'COMPLETED' => $completed,
			'DIRECTION' => $direction
		);

		ActivityChannelStatisticsTable::upsert($data);
	}
	/**
	 * Unregister Entity
	 * @param int $ownerID Owner ID.
	 * @param int $channelTypeID Channel Type ID.
	 * @return void
	 */
	public static function unregister($ownerID, $channelTypeID = 0)
	{
		$filter = array('OWNER_ID' => $ownerID);
		if($channelTypeID > 0)
		{
			$filter['CHANNEL_TYPE_ID'] = $channelTypeID;
		}
		ActivityChannelStatisticsTable::deleteByFilter($filter);
	}
	/**
	 * Get current instance
	 * @return ActivityChannelStatisticEntry
	 * */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new ActivityChannelStatisticEntry();
		}
		return self::$current;
	}
	/**
	 * Include language file
	 * @return void
	 */
	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}