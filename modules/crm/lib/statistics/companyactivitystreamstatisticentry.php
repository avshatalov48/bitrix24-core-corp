<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Crm\Activity\StatisticsStream;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Crm;
use Bitrix\Crm\Statistics\Entity\CompanyActivityStreamStatisticsTable;
class CompanyActivityStreamStatisticEntry
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

		$query = new Query(CompanyActivityStreamStatisticsTable::getEntity());
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

		$value = isset($options['VALUE']) && is_array($options['VALUE']) ? $options['VALUE'] : null;
		if(!$value)
			throw new Main\ArgumentException('Options VALUE must be specified.', 'VALUE');

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmCompany::GetListEx(
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
		$incomingQty = isset($value[StatisticsStream::Incoming]) ?  (int)$value[StatisticsStream::Incoming] : 0;
		$outgoingQty = isset($value[StatisticsStream::Outgoing]) ?  (int)$value[StatisticsStream::Outgoing] : 0;
		$reversingQty = isset($value[StatisticsStream::Reversing]) ?  (int)$value[StatisticsStream::Reversing] : 0;
		$missingQty = isset($value[StatisticsStream::Missing]) ?  (int)$value[StatisticsStream::Missing] : 0;

		if ($incomingQty === 0 && $outgoingQty === 0 && $reversingQty === 0)
		{
			CompanyActivityStreamStatisticsTable::delete(array(
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
			if($responsibleID === (int)$present['RESPONSIBLE_ID']
				&& $incomingQty === (int)$present['INCOMING_QTY']
				&& $outgoingQty === (int)$present['OUTGOING_QTY']
				&& $reversingQty === (int)$present['REVERSING_QTY']
				&& $missingQty === (int)$present['MISSING_QTY']
			)
				return true;

			if($responsibleID !== (int)$present['RESPONSIBLE_ID'])
			{
				CompanyActivityStreamStatisticsTable::synchronize(
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
			'INCOMING_QTY' => $incomingQty,
			'OUTGOING_QTY' => $outgoingQty,
			'REVERSING_QTY' => $reversingQty,
			'MISSING_QTY' => $missingQty
		);

		CompanyActivityStreamStatisticsTable::upsert($data);

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
		$subQuery->addFilter('=OWNER_TYPE_ID', \CCrmOwnerType::Company);
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

		CompanyActivityStreamStatisticsTable::deleteByOwner($ownerID);
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

		$query = new Query(CompanyActivityStreamStatisticsTable::getEntity());
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
			$dbResult = \CCrmCompany::GetListEx(
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

		CompanyActivityStreamStatisticsTable::synchronize(
			$ownerID,
			array('RESPONSIBLE_ID' => $responsibleID)
		);
		return true;
	}
}