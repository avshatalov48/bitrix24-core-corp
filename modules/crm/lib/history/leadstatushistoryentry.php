<?php
namespace Bitrix\Crm\History;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\History\Entity\LeadStatusHistoryTable;

class LeadStatusHistoryEntry
{
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

		$query = new Query(LeadStatusHistoryTable::getEntity());
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->addSelect('*');

		$results = array();
		$dbResult = $query->exec();
		while($fields = $dbResult->fetch())
		{
			$results[] = $fields;
		}
		return $results;
	}
	public static function getLatest($ownerID)
	{
		if(!is_int($ownerID))
		{
			$ownerID = (int)$ownerID;
		}

		if($ownerID <= 0)
		{
			throw new Main\ArgumentException('Owner ID must be greater than zero.', 'ownerID');
		}

		$subQuery = new Query(LeadStatusHistoryTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_ID', 'MAX(ID)'));
		$subQuery->addSelect('MAX_ID');
		$subQuery->addFilter('=OWNER_ID', $ownerID);

		$query = new Query(LeadStatusHistoryTable::getEntity());
		$query->addSelect('*');
		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.ID' => 'ref.MAX_ID'),
				array('join_type' => 'INNER')
			)
		);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result) ? $result : null;
	}
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

		$query = new Query(LeadStatusHistoryTable::getEntity());
		$query->addSelect('ID');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result);
	}
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

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STATUS_ID', 'ASSIGNED_BY_ID')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$statusID = isset($entityFields['STATUS_ID']) ? $entityFields['STATUS_ID'] : '';
		if($statusID === '')
		{
			return false;
		}

		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		$time = isset($options['TIME']) ? $options['TIME'] : null;
		if($time === null)
		{
			$time = new DateTime();
		}

		$month = (int)$time->format('m');
		$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		$year = (int)$time->format('Y');

		$semanticID = \CCrmLead::GetSemanticID($statusID);

		$isNew = isset($options['IS_NEW']) ? (bool)$options['IS_NEW'] : false;
		$typeID = PhaseSemantics::isFinal($semanticID)
			? HistoryEntryType::FINALIZATION : ($isNew ? HistoryEntryType::CREATION : HistoryEntryType::MODIFICATION);

		$date = Date::createFromTimestamp($time->getTimestamp());

		$latest = self::getLatest($ownerID);
		if(is_array($latest) && $latest['STATUS_ID'] === $statusID)
		{
			return false;
		}

		$result = LeadStatusHistoryTable::add(
			array(
				'TYPE_ID' => $typeID,
				'OWNER_ID' => $ownerID,
				'CREATED_TIME' => $time,
				'CREATED_DATE' =>  $date,
				'PERIOD_YEAR' => $year,
				'PERIOD_QUARTER' => $quarter,
				'PERIOD_MONTH' => $month,
				'RESPONSIBLE_ID' => $responsibleID,
				'STATUS_ID' => $statusID,
				'STATUS_SEMANTIC_ID' => $semanticID,
				'IS_IN_WORK' =>  !$isNew ? 'Y' : 'N',
				'IS_JUNK' =>  PhaseSemantics::isLost($semanticID) ? 'Y' : 'N'
			)
		);

		if($result->isSuccess()
			&& $result->getId() > 0
			&& is_array($latest)
			&& ((int)$latest['TYPE_ID']) === HistoryEntryType::FINALIZATION)
		{
			LeadStatusHistoryTable::delete($latest['ID']);
		}

		return true;
	}
	public static function unregister($ownerID)
	{
		LeadStatusHistoryTable::deleteByOwner($ownerID);
	}
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

		$query = new Query(LeadStatusHistoryTable::getEntity());
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

		LeadStatusHistoryTable::synchronize($ownerID, array('RESPONSIBLE_ID' => $responsibleID));
		return true;
	}
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
}