<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;

use Bitrix\Crm;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\LeadActivityStatisticsTable;

class LeadActivityStatisticEntry
{
	/**
	* @return array|null
	*/
	public static function get($ownerID, Date $date)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(LeadActivityStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addFilter('=DEADLINE_DATE', $date);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}
	/**
	* @return boolean
	*/
	public static function isRegistered($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$query = new Query(LeadActivityStatisticsTable::getEntity());
		$query->addSelect('DEADLINE_DATE');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result);
	}
	/**
	* @return boolean
	*/
	public static function register($ownerID, array $entityFields = null, array $options = null)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		if(!is_array($options))
		{
			$options = array();
		}

		/** @var Date $date */
		$date = isset($options['DATE']) ? $options['DATE'] : null;
		if($date === null)
		{
			$date = new Date();
		}

		$day = (int)$date->format('d');
		$month = (int)$date->format('m');
		$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		$year = (int)$date->format('Y');

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STATUS_ID', 'DATE_CREATE', 'ASSIGNED_BY_ID')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		/** @var Date $date */
		$createdDate = self::parseDateString(isset($entityFields['DATE_CREATE']) ? $entityFields['DATE_CREATE'] : '');

		$statusID = isset($entityFields['STATUS_ID']) ? $entityFields['STATUS_ID'] : '';
		$semanticID = \CCrmLead::GetSemanticID($statusID);
		$isJunk = PhaseSemantics::isLost($semanticID);
		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		$callQty = 0;
		$meetingQty = 0;
		$emailQty = 0;

		$startTime = new DateTime($date->format(DateTime::getFormat()));
		$endTime = new DateTime($date->format(DateTime::getFormat()));
		$endTime->setTime(23, 59, 59);

		$query = new Query(Crm\ActivityTable::getEntity());
		$query->addSelect('TYPE_ID');
		$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(*)'));
		$query->addSelect('QTY');

		$query->addFilter('=COMPLETED', 'Y');
		//$query->addFilter('=STATUS', \CCrmActivityStatus::Completed);

		$query->addFilter('>=DEADLINE', $startTime);
		$query->addFilter('<=DEADLINE', $endTime);
		$query->addGroup('TYPE_ID');

		$subQuery = new Query(Crm\ActivityBindingTable::getEntity());
		$subQuery->addSelect('ACTIVITY_ID');
		$subQuery->addFilter('=OWNER_TYPE_ID', \CCrmOwnerType::Lead);
		$subQuery->addFilter('=OWNER_ID', $ownerID);

		$query->registerRuntimeField('',
			new ReferenceField('B',
				Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.ACTIVITY_ID'),
				array('join_type' => 'INNER')
			)
		);

		\CTimeZone::Disable();
		$dbResult = $query->exec();
		\CTimeZone::Enable();

		while($stats = $dbResult->fetch())
		{
			$typeID = isset($stats['TYPE_ID']) ? (int)$stats['TYPE_ID'] : 0;
			$qty = isset($stats['QTY']) ? (int)$stats['QTY'] : 0;
			if($typeID === \CCrmActivityType::Call)
			{
				$callQty = $qty;
			}
			elseif($typeID === \CCrmActivityType::Meeting)
			{
				$meetingQty = $qty;
			}
			elseif($typeID === \CCrmActivityType::Email)
			{
				$emailQty = $qty;
			}
		}

		if($callQty === 0 && $meetingQty === 0 && $emailQty === 0)
		{
			LeadActivityStatisticsTable::delete(array('OWNER_ID' => $ownerID, 'DEADLINE_DATE' => $date));
			return true;
		}

		$present = self::get($ownerID, $date);
		if(is_array($present))
		{
			if($responsibleID === (int)$present['RESPONSIBLE_ID']
				&& $statusID === $present['STATUS_ID']
				&& $callQty === (int)$present['CALL_QTY']
				&& $meetingQty === (int)$present['MEETING_QTY']
				&& $emailQty === (int)$present['EMAIL_QTY'])
			{
				return false;
			}

			if($responsibleID !== (int)$present['RESPONSIBLE_ID'])
			{
				LeadActivityStatisticsTable::synchronize(
					$ownerID,
					array('RESPONSIBLE_ID' => $responsibleID)
				);
			}
		}

		$data = array(
			'OWNER_ID' => $ownerID,
			'DEADLINE_DATE' => $date,
			'DEADLINE_YEAR' => $year,
			'DEADLINE_QUARTER' => $quarter,
			'DEADLINE_MONTH' => $month,
			'DEADLINE_DAY' => $day,
			'CREATED_DATE' => $createdDate,
			'RESPONSIBLE_ID' => $responsibleID,
			'STATUS_SEMANTIC_ID' => $semanticID,
			'STATUS_ID' => $statusID,
			'IS_JUNK' => $isJunk ? 'Y' : 'N',
			'CALL_QTY' => $callQty,
			'MEETING_QTY' => $meetingQty,
			'EMAIL_QTY' => $emailQty
		);

		LeadActivityStatisticsTable::upsert($data);
		return true;
	}
	/**
	* @return string|null
	*/
	public static function parseDateString($str)
	{
		if($str === '')
		{
			return null;
		}

		try
		{
			$date = new Date($str, Date::convertFormatToPhp(FORMAT_DATE));
		}
		catch(Main\ObjectException $e)
		{
			try
			{
				$date = new DateTime($str, Date::convertFormatToPhp(FORMAT_DATETIME));
				$date->setTime(0, 0, 0);
			}
			catch(Main\ObjectException $e)
			{
				return null;
			}
		}
		return $date;
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
		$subQuery->addFilter('=OWNER_TYPE_ID', \CCrmOwnerType::Lead);
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

		LeadActivityStatisticsTable::deleteByOwner($ownerID);
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

		$query = new Query(LeadActivityStatisticsTable::getEntity());
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
			$dbResult = \CCrmLead::GetListEx(
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

		LeadActivityStatisticsTable::synchronize(
			$ownerID,
			array('RESPONSIBLE_ID' => $responsibleID)
		);
		return true;
	}
}