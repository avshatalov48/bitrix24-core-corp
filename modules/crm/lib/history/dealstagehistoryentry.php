<?php
namespace Bitrix\Crm\History;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\History\Entity\DealStageHistoryTable;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;

class DealStageHistoryEntry
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

		$query = new Query(DealStageHistoryTable::getEntity());
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

		$subQuery = new Query(DealStageHistoryTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_ID', 'MAX(ID)'));
		$subQuery->addSelect('MAX_ID');
		$subQuery->addFilter('=OWNER_ID', $ownerID);

		$query = new Query(DealStageHistoryTable::getEntity());
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

		$query = new Query(DealStageHistoryTable::getEntity());
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
		$forced = isset($options['FORCED']) ? $options['FORCED'] : false;

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$stageID = isset($entityFields['STAGE_ID']) ? $entityFields['STAGE_ID'] : '';
		if($stageID === '')
		{
			return false;
		}

		$categoryID = isset($entityFields['CATEGORY_ID']) ? (int)$entityFields['CATEGORY_ID'] : 0;
		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;
		$startDate = self::parseDateString(isset($entityFields['BEGINDATE']) ? $entityFields['BEGINDATE'] : '');
		if($startDate === null)
		{
			$startDate = new Date();
		}

		$endDate = self::parseDateString(isset($entityFields['CLOSEDATE']) ? $entityFields['CLOSEDATE'] : '');
		if($endDate === null)
		{
			$endDate = new Date('9999-12-31', 'Y-m-d');
		}

		$time = isset($options['TIME']) ? $options['TIME'] : null;
		if($time === null)
		{
			$time = new DateTime();
		}
		$date = Date::createFromTimestamp($time->getTimestamp());

		$month = (int)$time->format('m');
		$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		$year = (int)$time->format('Y');

		$startMonth = (int)$startDate->format('m');
		$startQuarter = $startMonth <= 3 ? 1 : ($startMonth <= 6 ? 2 : ($startMonth <= 9 ? 3 : 4));
		$startYear = (int)$startDate->format('Y');

		$endMonth = (int)$endDate->format('m');
		$endQuarter = $endMonth <= 3 ? 1 : ($endMonth <= 6 ? 2 : ($endMonth <= 9 ? 3 : 4));
		$endYear = (int)$endDate->format('Y');

		$semanticID = \CCrmDeal::GetSemanticID($stageID, $categoryID);
		$isLost = PhaseSemantics::isLost($semanticID);
		$isNew = isset($options['IS_NEW']) ? (bool)$options['IS_NEW'] : false;

		$typeID = PhaseSemantics::isFinal($semanticID)
			? HistoryEntryType::FINALIZATION
			: ($isNew ? HistoryEntryType::CREATION : HistoryEntryType::MODIFICATION);
		$effectiveDate = self::resolveEffectiveDate(
			array('TYPE_ID' => $typeID, 'CREATED_DATE' =>  $date, 'START_DATE' => $startDate, 'END_DATE' => $endDate)
		);

		$latest = self::getLatest($ownerID);
		if(is_array($latest) && $latest['STAGE_ID'] === $stageID)
		{
			if(!$forced)
			{
				return false;
			}

			DealStageHistoryTable::delete($latest['ID']);
		}

		$result = DealStageHistoryTable::add(
			array(
				'TYPE_ID' => $typeID,
				'OWNER_ID' => $ownerID,
				'CREATED_TIME' => $time,
				'CREATED_DATE' =>  $date,
				'EFFECTIVE_DATE' => $effectiveDate,
				'START_DATE' => $startDate,
				'END_DATE' => $endDate,
				'PERIOD_YEAR' => $year,
				'PERIOD_QUARTER' => $quarter,
				'PERIOD_MONTH' => $month,
				'START_PERIOD_YEAR' => $startYear,
				'START_PERIOD_QUARTER' => $startQuarter,
				'START_PERIOD_MONTH' => $startMonth,
				'END_PERIOD_YEAR' => $endYear,
				'END_PERIOD_QUARTER' => $endQuarter,
				'END_PERIOD_MONTH' => $endMonth,
				'RESPONSIBLE_ID' => $responsibleID,
				'CATEGORY_ID' => $categoryID,
				'STAGE_ID' => $stageID,
				'STAGE_SEMANTIC_ID' => $semanticID,
				'IS_LOST' =>  $isLost ? 'Y' : 'N'
			)
		);

		if($result->isSuccess()
			&& $result->getId() > 0
			&& is_array($latest)
			&& ((int)$latest['TYPE_ID']) === HistoryEntryType::FINALIZATION)
		{
			DealStageHistoryTable::delete($latest['ID']);
		}

		return true;
	}
	public static function unregister($ownerID)
	{
		DealStageHistoryTable::deleteByOwner($ownerID);
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

		$query = new Query(DealStageHistoryTable::getEntity());
		$query->addSelect('TYPE_ID');
		$query->addSelect('CREATED_DATE');
		$query->addSelect('START_DATE');
		$query->addSelect('END_DATE');
		$query->addSelect('EFFECTIVE_DATE');
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
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;
		$beginDate = isset($entityFields['BEGINDATE']) ? $entityFields['BEGINDATE'] : '';
		/** @var Date $startDate */
		$startDate = new Date($beginDate);

		$closeDate = isset($entityFields['CLOSEDATE']) ? $entityFields['CLOSEDATE'] : '';
		/** @var Date $endDate */
		$endDate = $closeDate !== '' ? new Date($closeDate) : new Date('9999-12-31', 'Y-m-d');

		if(isset($first['START_DATE'])
			&& isset($first['END_DATE'])
			&& $startDate->getTimestamp() === $first['START_DATE']->getTimestamp()
			&& $endDate->getTimestamp() === $first['END_DATE']->getTimestamp()
			&& $responsibleID === (int)$first['RESPONSIBLE_ID'])
		{
			return false;
		}

		DealStageHistoryTable::synchronize(
			$ownerID,
			array(
				'START_DATE' => $startDate,
				'END_DATE' => $endDate,
				'RESPONSIBLE_ID' => $responsibleID
			)
		);
		return true;
	}
	public static function processCagegoryChange($ownerID)
	{
		self::unregister($ownerID);
		self::register($ownerID);
	}
	protected static function parseDateString($str)
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
	protected static function resolveEffectiveDate(array $fields)
	{
		$typeID = isset($fields['TYPE_ID']) ? (int)$fields['TYPE_ID'] : HistoryEntryType::MODIFICATION;
		if($typeID === HistoryEntryType::CREATION && isset($fields['START_DATE']))
		{
			return $fields['START_DATE'];
		}
		elseif($typeID === HistoryEntryType::FINALIZATION && isset($fields['END_DATE']))
		{
			return $fields['END_DATE'];
		}

		return isset($fields['CREATED_DATE']) ? $fields['CREATED_DATE'] : null;
	}
}