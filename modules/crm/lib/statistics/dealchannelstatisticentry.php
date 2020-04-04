<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Statistics\Entity\DealChannelStatisticsTable;
use Bitrix\Crm\Integration\Channel\EntityChannelBinding;

class DealChannelStatisticEntry extends StatisticEntryBase
{
	/** @var DealChannelStatisticEntry|null */
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

		$query = new Query(DealChannelStatisticsTable::getEntity());
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


		$query = new Query(DealChannelStatisticsTable::getEntity());
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
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID',
					'BEGINDATE', 'CLOSEDATE', 'DATE_CREATE', 'DATE_MODIFY',
					'CURRENCY_ID', 'OPPORTUNITY', 'EXCH_RATE'
				)
			);

			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return;
			}
		}

		$stageID = isset($entityFields['STAGE_ID']) ? $entityFields['STAGE_ID'] : '';
		$categoryID = isset($entityFields['CATEGORY_ID']) ? (int)$entityFields['CATEGORY_ID'] : 0;
		$semanticID = \CCrmDeal::GetSemanticID($stageID, $categoryID);
		$isFinalized = PhaseSemantics::isFinal($semanticID);
		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		/** @var Date $startDate */
		$startDate = self::parseDateString(isset($entityFields['BEGINDATE']) ? $entityFields['BEGINDATE'] : '');
		if($startDate === null || $startDate->getTimestamp() === false)
		{
			$startDate = isset($entityFields['DATE_CREATE']) ? self::parseDateString($entityFields['DATE_CREATE']) : null;
			if($startDate === null || $startDate->getTimestamp() === false)
			{
				$startDate = new Date();
			}
		}

		/** @var Date $endDate */
		$endDate = self::parseDateString(isset($entityFields['CLOSEDATE']) ? $entityFields['CLOSEDATE'] : '');
		if($endDate === null || $endDate->getTimestamp() === false)
		{
			if(!$isFinalized)
			{
				$endDate = new Date('9999-12-31', 'Y-m-d');
			}
			else
			{
				//If CLOSEDATE is not defined for finalized deal, then try to take DATE_MODIFY.
				$endDate = isset($entityFields['DATE_MODIFY']) ? self::parseDateString($entityFields['DATE_MODIFY']) : null;
				if($endDate === null)
				{
					$endDate = new Date();
				}
			}
		}

		$date = $isFinalized ? $endDate : $startDate;

		$sumTotal = isset($entityFields['OPPORTUNITY']) ? (double)$entityFields['OPPORTUNITY'] : 0.0;
		$currencyID = isset($entityFields['CURRENCY_ID']) ? $entityFields['CURRENCY_ID'] : '';
		$accountingCurrencyID = \CCrmCurrency::GetAccountCurrencyID();
		if($currencyID !== $accountingCurrencyID)
		{
			$accData = \CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $currencyID,
					'SUM' => $sumTotal,
					'EXCH_RATE' => isset($entityFields['EXCH_RATE']) ? $entityFields['EXCH_RATE'] : null
				)
			);
			if(is_array($accData))
			{
				$sumTotal = (double)$accData['ACCOUNT_SUM'];
			}
		}

		$accountingDecimals = \CCrmCurrency::GetCurrencyDecimals($accountingCurrencyID);
		$sumTotal = round($sumTotal, $accountingDecimals);

		$latest = self::get($ownerID, $channelTypeID);
		if(is_array($latest)
			&& $responsibleID === (int)$latest['RESPONSIBLE_ID']
			&& $semanticID === $latest['STAGE_SEMANTIC_ID']
			&& $accountingCurrencyID === $latest['CURRENCY_ID']
			&& $sumTotal === round((double)$latest['SUM_TOTAL'], $accountingDecimals)
		)
		{
			return;
		}

		$data = array(
			'OWNER_ID' => $ownerID,
			'CREATED_DATE' => $date,
			'START_DATE' => $startDate,
			'END_DATE' => $endDate,
			'CHANNEL_TYPE_ID' => $channelTypeID,
			'CHANNEL_ORIGIN_ID' => isset($bindingParams['ORIGIN_ID']) ? $bindingParams['ORIGIN_ID'] : '',
			'CHANNEL_COMPONENT_ID' => isset($bindingParams['COMPONENT_ID']) ? $bindingParams['COMPONENT_ID'] : '',
			'RESPONSIBLE_ID' => $responsibleID,
			'STAGE_SEMANTIC_ID' => $semanticID,
			'CURRENCY_ID' => $accountingCurrencyID,
			'SUM_TOTAL' => $sumTotal
		);

		DealChannelStatisticsTable::upsert($data);
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
		DealChannelStatisticsTable::deleteByFilter($filter);
	}
	/**
	 * Get current instance
	 * @return DealChannelStatisticEntry
	 * */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new DealChannelStatisticEntry();
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