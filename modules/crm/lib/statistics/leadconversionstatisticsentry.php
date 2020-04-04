<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Crm;
use Bitrix\Crm\Statistics\Entity\LeadConversionStatisticsTable;

class LeadConversionStatisticsEntry
{
	/**
	* @return array
	*/
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

		$subQuery = new Query(LeadConversionStatisticsTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_ENTRY_DATE', 'MAX(ENTRY_DATE)'));
		$subQuery->addSelect('MAX_ENTRY_DATE');
		$subQuery->addFilter('=OWNER_ID', $ownerID);
		$subQuery->addGroup('OWNER_ID');

		$query = new Query(LeadConversionStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => new SqlExpression($ownerID), '=this.ENTRY_DATE' => 'ref.MAX_ENTRY_DATE'),
				array('join_type' => 'INNER')
			)
		);

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

		$query = new Query(LeadConversionStatisticsTable::getEntity());
		$query->addSelect('ENTRY_DATE');
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

		$isNew = isset($options['IS_NEW']) ? (bool)$options['IS_NEW'] : false;
		/** @var Date $createdDate */
		$createdDate = self::parseDateString(isset($entityFields['DATE_CREATE']) ? $entityFields['DATE_CREATE'] : '');
		/** @var Date $lastChangeDate */
		$lastChangeDate = self::parseDateString(isset($entityFields['DATE_MODIFY']) ? $entityFields['DATE_MODIFY'] : '');
		/** @var Date $date */
		$date = isset($options['DATE']) ? $options['DATE'] : null;
		if($date === null)
		{
			$date = $lastChangeDate !== null ? $lastChangeDate : new Date();
		}

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STATUS_ID', 'ASSIGNED_BY_ID')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$statusID = isset($entityFields['STATUS_ID']) ? $entityFields['STATUS_ID'] : '';
		if($statusID !== 'CONVERTED')
		{
			if(!$isNew)
			{
				LeadConversionStatisticsTable::deleteByOwner($ownerID);
			}
			return false;
		}

		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		//region CONTACT_QTY, COMPANY_QTY, DEAL_QTY
		$contactQty = self::getBindingCount($ownerID, \CCrmOwnerType::Contact);
		$companyQty = self::getBindingCount($ownerID, \CCrmOwnerType::Company);
		$dealQty = self::getBindingCount($ownerID, \CCrmOwnerType::Deal);
		//endregion

		$latest = self::getLatest($ownerID);
		if(is_array($latest))
		{
			//Update responsible user in all related records
			if($responsibleID !== (int)$latest['RESPONSIBLE_ID'])
			{
				LeadConversionStatisticsTable::synchronize($ownerID, array('RESPONSIBLE_ID' => $responsibleID));
			}

			if($contactQty === (int)$latest['CONTACT_QTY']
				&& $companyQty === (int)$latest['COMPANY_QTY']
				&& $dealQty === (int)$latest['DEAL_QTY'])
			{
				return false;
			}
		}

		//region TOTALS_DATE
		$latestTotals = LeadSumStatisticEntry::getLatest($ownerID);
		/** @var Date $totalsDate */
		$totalsDate = is_array($latestTotals) && isset($latestTotals['CREATED_DATE'])
			? $latestTotals['CREATED_DATE'] : null;
		//endregion

		LeadConversionStatisticsTable::upsert(
			array(
				'OWNER_ID' => $ownerID,
				'ENTRY_DATE' => $date,
				'CREATED_DATE' => $createdDate,
				'TOTALS_DATE' => $totalsDate,
				'RESPONSIBLE_ID' => $responsibleID,
				'CONTACT_QTY' => $contactQty,
				'COMPANY_QTY' => $companyQty,
				'DEAL_QTY' => $dealQty
			)
		);
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
	* @return void
	*/
	public static function unregister($ownerID)
	{
		LeadConversionStatisticsTable::deleteByOwner($ownerID);
	}
	/**
	* @return boolean
	*/
	public static function processBindingsChange($ownerID)
	{
		$dbResult = \CCrmLead::GetListEx(
			array(),
			array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('STATUS_ID', 'ASSIGNED_BY_ID')
		);
		$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($entityFields))
		{
			return false;
		}

		$statusID = isset($entityFields['STATUS_ID']) ? $entityFields['STATUS_ID'] : '';
		if($statusID !== 'CONVERTED')
		{
			return false;
		}

		return self::register($ownerID, $entityFields);
	}
	/**
	* @return integer
	*/
	protected static function getBindingCount($ownerID, $bindingTypeID)
	{
		if($bindingTypeID === \CCrmOwnerType::Contact)
		{
			//Only one Contact is allowed per Lead
			$query = new Query(Crm\ContactTable::getEntity());
			$query->registerRuntimeField('', new ExpressionField('X', '1'));
			$query->addFilter('=LEAD_ID', $ownerID);
			$results = $query->exec()->fetch();
			return is_array($results) ? 1 : 0;
		}
		elseif($bindingTypeID === \CCrmOwnerType::Company)
		{
			//Only one Company is allowed per Lead
			$query = new Query(Crm\CompanyTable::getEntity());
			$query->registerRuntimeField('', new ExpressionField('X', '1'));
			$query->addFilter('=LEAD_ID', $ownerID);
			$results = $query->exec()->fetch();
			return is_array($results) ? 1 : 0;
		}
		elseif($bindingTypeID === \CCrmOwnerType::Deal)
		{
			$query = new Query(Crm\DealTable::getEntity());
			$query->registerRuntimeField('', new ExpressionField('QTY', 'COUNT(*)'));
			$query->addSelect('QTY');
			$query->addFilter('=LEAD_ID', $ownerID);
			$results = $query->exec()->fetch();
			return isset($results['QTY']) ? (int)$results['QTY'] : 0;
		}

		return 0;
	}
}