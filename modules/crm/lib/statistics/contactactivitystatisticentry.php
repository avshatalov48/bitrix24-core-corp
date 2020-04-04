<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Crm;
use Bitrix\Crm\Statistics\Entity\ContactActivityStatisticsTable;
class ContactActivityStatisticEntry
{
	/**
	* @return array|null
	*/
	public static function get($ownerID, Date $date, $providerId, $providerTypeId)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(ContactActivityStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addFilter('=DEADLINE_DATE', $date);
		$query->addFilter('=PROVIDER_ID', $providerId);
		$query->addFilter('=PROVIDER_TYPE_ID', $providerTypeId);

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

		$value = isset($options['VALUE']) ? $options['VALUE'] : null;
		if($value === null)
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
		$value = (int)$value;

		if ($value === 0)
		{
			ContactActivityStatisticsTable::delete(array(
				'OWNER_ID' => $ownerID,
				'DEADLINE_DATE' => $date,
				'PROVIDER_ID' => $providerId,
				'PROVIDER_TYPE_ID' => $providerTypeId
			));
			return true;
		}

		$present = self::get($ownerID, $date, $providerId, $providerTypeId);
		if(is_array($present))
		{
			if($responsibleID === (int)$present['RESPONSIBLE_ID'] && $value === (int)$present['TOTAL_QTY'])
				return true;

			if($responsibleID !== (int)$present['RESPONSIBLE_ID'])
			{
				ContactActivityStatisticsTable::synchronize(
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
			'RESPONSIBLE_ID' => $responsibleID,
			'TOTAL_QTY' => $value
		);

		ContactActivityStatisticsTable::upsert($data);

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

		ContactActivityStatisticsTable::deleteByOwner($ownerID);
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

		$query = new Query(ContactActivityStatisticsTable::getEntity());
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

		ContactActivityStatisticsTable::synchronize(
			$ownerID,
			array('RESPONSIBLE_ID' => $responsibleID)
		);
		return true;
	}
}