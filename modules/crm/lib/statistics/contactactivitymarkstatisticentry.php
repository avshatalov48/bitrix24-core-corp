<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Crm\Activity\StatisticsMark;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Crm;
use Bitrix\Crm\Statistics\Entity\ContactActivityMarkStatisticsTable;
class ContactActivityMarkStatisticEntry
{
	/**
	 * @param $ownerID
	 * @param Date $date
	 * @param $providerId
	 * @param $providerTypeId
	 * @param $sourceId
	 * @return array|null
	 * @throws Main\ArgumentException
	 */
	public static function get($ownerID, Date $date, $providerId, $providerTypeId, $sourceId)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(ContactActivityMarkStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addFilter('=DEADLINE_DATE', $date);
		$query->addFilter('=PROVIDER_ID', $providerId);
		$query->addFilter('=PROVIDER_TYPE_ID', $providerTypeId);
		$query->addFilter('=SOURCE_ID', $sourceId);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}

	/**
	 * @param $ownerID
	 * @param array $entityFields
	 * @param array $options
	 * @return bool
	 * @throws Main\ArgumentException
	 * @throws Main\NotSupportedException
	 * @throws \Exception
	 */
	public static function register($ownerID, array $entityFields = null, array $options = null)
	{
		if(!is_int($ownerID))
			$ownerID = (int)$ownerID;

		if($ownerID <= 0)
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');

		if(!is_array($options))
			$options = array();

		$date = isset($options['DATE']) ? $options['DATE'] : null;
		if(!$date)
			throw new Main\ArgumentException('Options DATE must be specified.', 'DATE');

		$providerId = isset($options['PROVIDER_ID']) ? $options['PROVIDER_ID'] : null;
		if(!$providerId)
			throw new Main\ArgumentException('Options PROVIDER_ID must be specified.', 'PROVIDER_ID');
		
		$providerTypeId = isset($options['PROVIDER_TYPE_ID']) ? $options['PROVIDER_TYPE_ID'] : null;
		if(!$providerTypeId)
			throw new Main\ArgumentException('Options PROVIDER_TYPE_ID must be specified.', 'PROVIDER_TYPE_ID');

		$sourceId = isset($options['SOURCE_ID']) ? $options['SOURCE_ID'] : null;
		if(!$sourceId)
			throw new Main\ArgumentException('Options SOURCE_ID must be specified.', 'SOURCE_ID');

		$value = isset($options['VALUE']) && is_array($options['VALUE']) ? $options['VALUE'] : null;
		if(!$value)
			throw new Main\ArgumentException('Options VALUE must be specified.', 'VALUE');

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmContact::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ASSIGNED_BY_ID')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;
		$none = isset($value[StatisticsMark::None]) ?  (int)$value[StatisticsMark::None] : 0;
		$positive = isset($value[StatisticsMark::Positive]) ?  (int)$value[StatisticsMark::Positive] : 0;
		$negative = isset($value[StatisticsMark::Negative]) ?  (int)$value[StatisticsMark::Negative] : 0;

		if ($none === 0 && $positive === 0 && $negative === 0)
		{
			ContactActivityMarkStatisticsTable::delete(array(
				'OWNER_ID' => $ownerID,
				'DEADLINE_DATE' => $date,
				'PROVIDER_ID' => $providerId,
				'PROVIDER_TYPE_ID' => $providerTypeId,
				'SOURCE_ID' => $sourceId
			));
			return true;
		}

		$present = self::get($ownerID, $date, $providerId, $providerTypeId, $sourceId);
		if(is_array($present))
		{
			if($responsibleID === (int)$present['RESPONSIBLE_ID']
				&& $none === (int)$present['NONE_QTY']
				&& $positive === (int)$present['POSITIVE_QTY']
				&& $negative === (int)$present['NEGATIVE_QTY']
			)
				return true;

			if($responsibleID !== (int)$present['RESPONSIBLE_ID'])
			{
				ContactActivityMarkStatisticsTable::synchronize(
					$ownerID,
					array('RESPONSIBLE_ID' => $responsibleID)
				);
			}
		}

		$data = array(
			'OWNER_ID' => $ownerID,
			'DEADLINE_DATE' => $date,
			'PROVIDER_ID' => $providerId,
			'PROVIDER_TYPE_ID' => $providerTypeId,
			'SOURCE_ID' => $sourceId,
			'RESPONSIBLE_ID' => $responsibleID,
			'NONE_QTY' => $none,
			'POSITIVE_QTY' => $positive,
			'NEGATIVE_QTY' => $negative
		);

		ContactActivityMarkStatisticsTable::upsert($data);

		return true;
	}
	/**
	* @return array
	*/
	public static function prepareTimeline($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(Crm\ActivityTable::getEntity());
		$query->addFilter('=COMPLETED', 'Y');

		$connection = Main\Application::getConnection();
		if($connection instanceof Main\DB\MysqlCommonConnection)
		{
			$query->registerRuntimeField('', new ExpressionField('DEADLINE_DATE', 'DATE(DEADLINE)'));
		}
		elseif($connection instanceof Main\DB\MssqlConnection)
		{
			$query->registerRuntimeField('', new ExpressionField('DEADLINE_DATE', 'CAST(FLOOR(CAST(DEADLINE AS FLOAT)) AS DATETIME)'));
		}
		elseif($connection instanceof Main\DB\OracleConnection)
		{
			$query->registerRuntimeField('', new ExpressionField('DEADLINE_DATE', 'TRUNC(DEADLINE)'));
		}
		$query->addSelect('DEADLINE_DATE');
		$query->addGroup('DEADLINE_DATE');

		$subQuery = new Query(Crm\ActivityBindingTable::getEntity());
		$subQuery->addSelect('ACTIVITY_ID');
		$subQuery->addFilter('=OWNER_TYPE_ID', \CCrmOwnerType::Contact);
		$subQuery->addFilter('=OWNER_ID', $ownerID);

		$query->registerRuntimeField('',
			new ReferenceField('B',
				Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.ACTIVITY_ID'),
				array('join_type' => 'INNER')
			)
		);

		$dbResult = $query->exec();
		$dates = array();
		while($fieilds = $dbResult->fetch())
		{
			$dates[] = $fieilds['DEADLINE_DATE'];
		}
		return $dates;
	}
	/**
	* @return void
	*/
	public static function unregister($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		ContactActivityMarkStatisticsTable::deleteByOwner($ownerID);
	}
	/**
	* @return boolean
	*/
	public static function synchronize($ownerID, array $entityFields = null)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(ContactActivityMarkStatisticsTable::getEntity());
		$query->addSelect('RESPONSIBLE_ID');

		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$first = $dbResult->fetch();
		if(!is_array($first))
		{
			return false;
		}

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmContact::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ASSIGNED_BY_ID')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;
		if($responsibleID === (int)$first['RESPONSIBLE_ID'])
		{
			return false;
		}

		ContactActivityMarkStatisticsTable::synchronize(
			$ownerID,
			array('RESPONSIBLE_ID' => $responsibleID)
		);
		return true;
	}
}