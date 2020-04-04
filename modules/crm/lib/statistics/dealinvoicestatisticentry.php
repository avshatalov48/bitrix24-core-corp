<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Crm\Statistics\Entity\DealInvoiceStatisticsTable;

class DealInvoiceStatisticEntry
{
	/**
	* @return array|null
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

		$subQuery = new Query(DealInvoiceStatisticsTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(CREATED_DATE)'));
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->addFilter('=OWNER_ID', $ownerID);
		$subQuery->addGroup('OWNER_ID');

		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		$query->addSelect('*');
		$query->registerRuntimeField('',
			new ReferenceField('M',
				Base::getInstanceByQuery($subQuery),
				array('=this.OWNER_ID' => new SqlExpression($ownerID), '=this.CREATED_DATE' => 'ref.MAX_CREATED_DATE'),
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

		$query = new Query(DealInvoiceStatisticsTable::getEntity());
		$query->addSelect('CREATED_DATE');
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

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE', 'OPPORTUNITY', 'CURRENCY_ID')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$sum = isset($entityFields['OPPORTUNITY']) ? (double)$entityFields['OPPORTUNITY'] : 0.0;
		$currencyID = isset($entityFields['CURRENCY_ID']) ? $entityFields['CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = \CCrmCurrency::GetBaseCurrencyID();
		}

		$sumData = \CCrmAccountingHelper::PrepareAccountingData(
			array('CURRENCY_ID' => $currencyID, 'SUM' => $sum)
		);

		if(is_array($sumData))
		{
			$sum = (double)$sumData['ACCOUNT_SUM'];
		}

		$categoryID = isset($entityFields['CATEGORY_ID']) ? (int)$entityFields['CATEGORY_ID'] : 0;
		$stageID = isset($entityFields['STAGE_ID']) ? $entityFields['STAGE_ID'] : '';
		$semanticID = \CCrmDeal::GetSemanticID($stageID, $categoryID);
		$isLost = PhaseSemantics::isLost($semanticID);

		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		/** @var Date $startDate */
		$startDate = self::parseDateString(isset($entityFields['BEGINDATE']) ? $entityFields['BEGINDATE'] : '');
		if($startDate === null)
		{
			$startDate = new Date();
		}

		/** @var Date $endDate */
		$endDate = self::parseDateString(isset($entityFields['CLOSEDATE']) ? $entityFields['CLOSEDATE'] : '');
		if($endDate === null)
		{
			$endDate = new Date('9999-12-31', 'Y-m-d');
		}


		$accountCurrencyID = \CCrmCurrency::GetAccountCurrencyID();

		$results = array();
		$skipHistory = isset($options['SKIP_HISTORY']) ? (bool)$options['SKIP_HISTORY'] : false;
		if(!$skipHistory)
		{
			$dbResult = \CCrmInvoice::GetList(
				array('DATE_BILL' => 'ASC'),
				array('UF_DEAL_ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'PRICE', 'CURRENCY', 'DATE_BILL')
			);

			if(is_object($dbResult))
			{
				while($invoiceFields = $dbResult->Fetch())
				{
					$key = isset($invoiceFields['DATE_BILL']) ? $invoiceFields['DATE_BILL'] : '';
					if($key === '')
					{
						continue;
					}

					$date = new Date($key);
					if(!isset($results[$key]))
					{
						$results[$key] = array('DATE' => $date, 'QTY' => 0, 'SUM' => 0.0);
					}
					$results[$key]['QTY']++;

					$currencyID = isset($invoiceFields['CURRENCY']) ? $invoiceFields['CURRENCY'] : '';
					$total = isset($invoiceFields['PRICE']) ? (double)$invoiceFields['PRICE'] : 0.0;

					$accData = \CCrmAccountingHelper::PrepareAccountingData(
						array('CURRENCY_ID' => $currencyID, 'SUM' => $total)
					);

					if(is_array($accData))
					{
						$results[$key]['SUM'] += (double)$accData['ACCOUNT_SUM'];
					}
				}
			}
		}

		DealInvoiceStatisticsTable::deleteByOwner($ownerID);

		if(empty($results))
		{
			if($semanticID === PhaseSemantics::SUCCESS)
			{
				//Creation of stub for successfully completed entity without invoices
				self::innerRegister(
					array(
						'OWNER_ID' => $ownerID,
						'CREATED_DATE' => new Date(),
						'START_DATE' => $startDate,
						'END_DATE' => $endDate,
						'RESPONSIBLE_ID' => $responsibleID,
						'CATEGORY_ID' => $categoryID,
						'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS,
						'STAGE_ID' => $stageID,
						'IS_LOST' => 'N',
						'CURRENCY_ID' => $accountCurrencyID,
						'INVOICE_SUM' => 0.0,
						'INVOICE_QTY' => 0,
						'TOTAL_INVOICE_SUM' => 0.0,
						'TOTAL_INVOICE_QTY' => 0,
						'TOTAL_SUM' => $sum
					)
				);
			}
			return true;
		}

		$totals = array('QTY' => 0, 'SUM' => 0.0);
		foreach($results as $result)
		{
			$totals['QTY'] += $result['QTY'];
			$totals['SUM'] += $result['SUM'];

			self::innerRegister(
				array(
					'OWNER_ID' => $ownerID,
					'CREATED_DATE' => $result['DATE'],
					'START_DATE' => $startDate,
					'END_DATE' => $endDate,
					'RESPONSIBLE_ID' => $responsibleID,
					'CATEGORY_ID' => $categoryID,
					'STAGE_SEMANTIC_ID' => $semanticID,
					'STAGE_ID' => $stageID,
					'IS_LOST' => $isLost ? 'Y' : 'N',
					'CURRENCY_ID' => $accountCurrencyID,
					'INVOICE_SUM' => $result['SUM'],
					'INVOICE_QTY' => $result['QTY'],
					'TOTAL_INVOICE_SUM' => $totals['SUM'],
					'TOTAL_INVOICE_QTY' => $totals['QTY'],
					'TOTAL_SUM' => $sum
				)
			);
		}
		return true;
	}
	/**
	* @return void
	*/
	public static function unregister($ownerID)
	{
		DealInvoiceStatisticsTable::deleteByOwner($ownerID);
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

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE', 'OPPORTUNITY', 'CURRENCY_ID')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return false;
			}
		}

		$categoryID = isset($entityFields['CATEGORY_ID']) ? (int)$entityFields['CATEGORY_ID'] : 0;
		$stageID = isset($entityFields['STAGE_ID']) ? $entityFields['STAGE_ID'] : '';
		$semanticID = \CCrmDeal::GetSemanticID($stageID, $categoryID);
		$isLost = PhaseSemantics::isLost($semanticID);
		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		$zeroDate = new Date('0000-00-00', 'Y-m-d');
		/** @var Date $startDate */
		$startDate = self::parseDateString(isset($entityFields['BEGINDATE']) ? $entityFields['BEGINDATE'] : '');
		if($startDate === null || $startDate == $zeroDate || $startDate->getTimestamp() === false)
		{
			$startDate = new Date();
		}

		/** @var Date $endDate */
		$endDate = self::parseDateString(isset($entityFields['CLOSEDATE']) ? $entityFields['CLOSEDATE'] : '');
		if($endDate === null || $endDate == $zeroDate || $endDate->getTimestamp() === false)
		{
			$endDate = new Date('9999-12-31', 'Y-m-d');
		}

		$sum = isset($entityFields['OPPORTUNITY']) ? (double)$entityFields['OPPORTUNITY'] : 0.0;
		$currencyID = isset($entityFields['CURRENCY_ID']) ? $entityFields['CURRENCY_ID'] : '';
		if($currencyID === '')
		{
			$currencyID = \CCrmCurrency::GetBaseCurrencyID();
		}
		$accountCurrencyID = \CCrmCurrency::GetAccountCurrencyID();
		$sumData = \CCrmAccountingHelper::PrepareAccountingData(
			array('CURRENCY_ID' => $currencyID, 'SUM' => $sum)
		);

		if(is_array($sumData))
		{
			$sum = (double)$sumData['ACCOUNT_SUM'];
		}

		$latest = self::getLatest($ownerID);
		if(!is_array($latest))
		{
			if($semanticID === PhaseSemantics::SUCCESS)
			{
				//Creation of stub for successfully completed entity without invoices
				self::innerRegister(
					array(
						'OWNER_ID' => $ownerID,
						'CREATED_DATE' => new Date(),
						'START_DATE' => $startDate,
						'END_DATE' => $endDate,
						'RESPONSIBLE_ID' => $responsibleID,
						'STAGE_SEMANTIC_ID' => PhaseSemantics::SUCCESS,
						'STAGE_ID' => $stageID,
						'IS_LOST' => 'N',
						'CURRENCY_ID' => $accountCurrencyID,
						'INVOICE_SUM' => 0.0,
						'INVOICE_QTY' => 0,
						'TOTAL_INVOICE_SUM' => 0.0,
						'TOTAL_INVOICE_QTY' => 0,
						'TOTAL_SUM' => $sum
					)
				);
			}
			return true;
		}

		if($startDate == $latest['START_DATE']
			&& $endDate == $latest['END_DATE']
			&& $responsibleID === (int)$latest['RESPONSIBLE_ID']
			&& $stageID === $latest['STAGE_ID']
			&& $semanticID === $latest['STAGE_SEMANTIC_ID']
			&& $sum === (double)$latest['TOTAL_SUM'])
		{
			return false;
		}

		if($semanticID !== $latest['STAGE_SEMANTIC_ID']
			&& $latest['STAGE_SEMANTIC_ID'] === PhaseSemantics::SUCCESS
			&& (int)$latest['INVOICE_QTY'] === 0)
		{
			//Clean up stub for successfully completed entity without invoices
			DealInvoiceStatisticsTable::delete(array('OWNER_ID' => $ownerID, 'CREATED_DATE' => $latest['CREATED_DATE']));
		}
		else
		{
			DealInvoiceStatisticsTable::synchronize(
				$ownerID,
				array(
					'START_DATE' => $startDate,
					'END_DATE' => $endDate,
					'RESPONSIBLE_ID' => $responsibleID,
					'STAGE_SEMANTIC_ID' => $semanticID,
					'STAGE_ID' => $stageID,
					'IS_LOST' => $isLost ? 'Y' : 'N',
					'TOTAL_SUM' => $sum
				)
			);
		}
		return true;
	}
	public static function processCagegoryChange($ownerID)
	{
		self::unregister($ownerID);
		self::register($ownerID);
	}
	/**
	* @return string|null
	*/
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
	/**
	* @return void
	*/
	protected static function innerRegister(array $data)
	{
		$date = isset($data['CREATED_DATE']) ? $data['CREATED_DATE'] : new Date();
		$day = (int)$date->format('d');
		$month = (int)$date->format('m');
		$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		$year = (int)$date->format('Y');

		DealInvoiceStatisticsTable::upsert(
			array(
				'CREATED_DATE' => $date,
				'PERIOD_YEAR' => $year,
				'PERIOD_QUARTER' => $quarter,
				'PERIOD_MONTH' => $month,
				'PERIOD_DAY' => $day,
				'OWNER_ID' => isset($data['OWNER_ID']) ? $data['OWNER_ID'] : 0,
				'START_DATE' => isset($data['START_DATE']) ? $data['START_DATE'] : new Date(),
				'END_DATE' => isset($data['END_DATE']) ? $data['END_DATE'] : new Date(),
				'RESPONSIBLE_ID' => isset($data['RESPONSIBLE_ID']) ? $data['RESPONSIBLE_ID'] : 0,
				'CATEGORY_ID' => isset($data['CATEGORY_ID']) ? $data['CATEGORY_ID'] : 0,
				'STAGE_SEMANTIC_ID' => isset($data['STAGE_SEMANTIC_ID']) ? $data['STAGE_SEMANTIC_ID'] : '',
				'STAGE_ID' => isset($data['STAGE_ID']) ? $data['STAGE_ID'] : '',
				'IS_LOST' => isset($data['IS_LOST']) ? $data['IS_LOST'] : 'N',
				'CURRENCY_ID' => isset($data['CURRENCY_ID']) ? $data['CURRENCY_ID'] : '',
				'INVOICE_SUM' => isset($data['INVOICE_SUM']) ? $data['INVOICE_SUM'] : 0.0,
				'INVOICE_QTY' => isset($data['INVOICE_QTY']) ? $data['INVOICE_QTY'] : 0,
				'TOTAL_INVOICE_SUM' => isset($data['TOTAL_INVOICE_SUM']) ? $data['TOTAL_INVOICE_SUM'] : 0.0,
				'TOTAL_INVOICE_QTY' => isset($data['TOTAL_INVOICE_QTY']) ? $data['TOTAL_INVOICE_QTY'] : 0,
				'TOTAL_SUM' => isset($data['TOTAL_SUM']) ? $data['TOTAL_SUM'] : 0.0
			)
		);
	}
}