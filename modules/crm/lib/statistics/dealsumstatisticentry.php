<?php
namespace Bitrix\Crm\Statistics;

use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Crm\Statistics\Entity\DealSumStatisticsTable;

class DealSumStatisticEntry
	extends StatisticEntryBase
	implements StatisticEntry
{
	const TYPE_NAME = 'DEAL_SUM_STATS';
	/** @var DealSumStatisticEntry|null  */
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

		$query = new Query(DealSumStatisticsTable::getEntity());
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
	 * Get latest
	 * @param int $ownerID Owner ID.
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

		$subQuery = new Query(DealSumStatisticsTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(CREATED_DATE)'));
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->addFilter('=OWNER_ID', $ownerID);
		$subQuery->addGroup('OWNER_ID');

		$query = new Query(DealSumStatisticsTable::getEntity());
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
	 * Check if entity is registered
	 * @param int $ownerID Owner ID.
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

		$query = new Query(DealSumStatisticsTable::getEntity());
		$query->addSelect('CREATED_DATE');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result);
	}
	/**
	 * Register Entity
	 * @param int $ownerID Owner ID.
	 * @params array $entityFields Entity fields.
	 * @return void
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

		if(!is_array($entityFields))
		{
			$dbResult = \CCrmDeal::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'BEGINDATE', 'CLOSEDATE', 'CURRENCY_ID', 'OPPORTUNITY', 'EXCH_RATE', 'UF_*')
			);
			$entityFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($entityFields))
			{
				return;
			}
		}

		if(!is_array($options))
		{
			$options = array();
		}
		$forced = isset($options['FORCED']) ? $options['FORCED'] : false;

		$bindingMap = self::getCurrent()->getSlotBindingMap();

		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;
		$categoryID = isset($entityFields['CATEGORY_ID']) ? (int)$entityFields['CATEGORY_ID'] : 0;
		$stageID = isset($entityFields['STAGE_ID']) ? $entityFields['STAGE_ID'] : '';
		$semanticID = \CCrmDeal::GetSemanticID($stageID, $categoryID);
		$isLost = PhaseSemantics::isLost($semanticID);
		$isFinalized = PhaseSemantics::isFinal($semanticID);

		$zeroDate = new Date('0000-00-00', 'Y-m-d');
		/** @var Date $startDate */
		$startDate = self::parseDateString(isset($entityFields['BEGINDATE']) ? $entityFields['BEGINDATE'] : '');
		if($startDate === null || $startDate == $zeroDate || $startDate->getTimestamp() === false)
		{
			$startDate = isset($entityFields['DATE_CREATE']) ? self::parseDateString($entityFields['DATE_CREATE']) : null;
			if($startDate === null || $startDate == $zeroDate || $startDate->getTimestamp() === false)
			{
				$startDate = new Date();
			}
		}

		/** @var Date $endDate */
		$endDate = self::parseDateString(isset($entityFields['CLOSEDATE']) ? $entityFields['CLOSEDATE'] : '');
		if($endDate === null || $endDate == $zeroDate || $endDate->getTimestamp() === false)
		{
			$endDate = new Date('9999-12-31', 'Y-m-d');
		}

		$date = $isFinalized ? $endDate : $startDate;
		$day = (int)$date->format('d');
		$month = (int)$date->format('m');
		$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		$year = (int)$date->format('Y');

		$currencyID = isset($entityFields['CURRENCY_ID']) ? $entityFields['CURRENCY_ID'] : '';
		$accountingCurrencyID = \CCrmCurrency::GetAccountCurrencyID();
		$sum = isset($entityFields['OPPORTUNITY']) ? (double)$entityFields['OPPORTUNITY'] : 0.0;
		$binding = $bindingMap->get('SUM_TOTAL');
		if($binding === null)
		{
			$total = isset($entityFields['OPPORTUNITY']) ? (double)$entityFields['OPPORTUNITY'] : 0.0;
		}
		else
		{
			$bindingFieldName = $binding->getFieldName();
			if($bindingFieldName === '')
			{
				$bindingFieldName = 'OPPORTUNITY';
			}
			$total = isset($entityFields[$bindingFieldName]) ? (double)$entityFields[$bindingFieldName] : 0.0;

			if($bindingFieldName !== 'OPPORTUNITY' && $binding->getOption('ADD_PRODUCT_ROW_SUM') === 'Y')
			{
				$total += $sum;
			}
		}

		if($currencyID !== $accountingCurrencyID)
		{
			$accData = \CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $currencyID,
					'SUM' => $total,
					'EXCH_RATE' => isset($entityFields['EXCH_RATE']) ? $entityFields['EXCH_RATE'] : null
				)
			);
			if(is_array($accData))
			{
				$total = (double)$accData['ACCOUNT_SUM'];
			}
		}

		$sumSlots = array();
		$sumSlotFields = DealSumStatisticsTable::getSumSlotFieldNames();
		foreach($sumSlotFields as $fieldName)
		{
			$binding = $bindingMap->get($fieldName);
			if($binding === null)
			{
				$slotSum = 0.0;
			}
			else
			{
				$bindingFieldName = $binding->getFieldName();
				$slotSum = $bindingFieldName !== '' && isset($entityFields[$bindingFieldName])
					? (double)$entityFields[$bindingFieldName] : 0.0;

				if($binding->getOption('ADD_PRODUCT_ROW_SUM') === 'Y')
				{
					$slotSum += $sum;
				}
			}

			if($currencyID !== $accountingCurrencyID)
			{
				$accData = \CCrmAccountingHelper::PrepareAccountingData(
					array(
						'CURRENCY_ID' => $currencyID,
						'SUM' => $slotSum,
						'EXCH_RATE' => isset($entityFields['EXCH_RATE']) ? $entityFields['EXCH_RATE'] : null
					)
				);
				if(is_array($accData))
				{
					$slotSum = (double)$accData['ACCOUNT_SUM'];
				}
			}

			$sumSlots[$fieldName] = $slotSum;
		}

		$latest = self::getLatest($ownerID);
		if(is_array($latest))
		{
			if($forced)
			{
				//Remove finalizing record(s).
				//We will insert new finalizing record at the end of this method.
				DealSumStatisticsTable::deleteByFilter(
					array(
						'OWNER_ID' => $ownerID,
						'SEMANTIC_ID' => PhaseSemantics::getFinalSemantis()
					)
				);

				DealSumStatisticsTable::synchronizeSumFields(
					$ownerID,
					array_merge(array('SUM_TOTAL' => $total), $sumSlots)
				);
			}

			if(isset($latest['CREATED_DATE']) && isset($latest['START_DATE']) && isset($latest['END_DATE']))
			{
				if(!$forced
					&& $date == $latest['CREATED_DATE']
					&& $startDate == $latest['START_DATE']
					&& $endDate == $latest['END_DATE']
					&& $responsibleID === (int)$latest['RESPONSIBLE_ID']
					&& $stageID === $latest['STAGE_ID']
					&& $currencyID === $latest['CURRENCY_ID']
					&& $total === (double)$latest['SUM_TOTAL']
					&& $sumSlots['UF_SUM_1'] === (double)$latest['UF_SUM_1']
					&& $sumSlots['UF_SUM_2'] === (double)$latest['UF_SUM_2']
					&& $sumSlots['UF_SUM_3'] === (double)$latest['UF_SUM_3']
					&& $sumSlots['UF_SUM_4'] === (double)$latest['UF_SUM_4']
					&& $sumSlots['UF_SUM_5'] === (double)$latest['UF_SUM_5']
				)
				{
					//Skip upsert if nothing changed.
					return;
				}

				if( $date->getTimestamp() !== $latest['CREATED_DATE']->getTimestamp()
					|| $startDate->getTimestamp() !== $latest['START_DATE']->getTimestamp()
					|| $endDate->getTimestamp() !== $latest['END_DATE']->getTimestamp()
					|| $responsibleID !== (int)$latest['RESPONSIBLE_ID']
				) {
					//Synchronize dates and responsible user.
					if(!$isFinalized)
					{
						//Deal is not finalized. Clear final item and synchronize process item.
						DealSumStatisticsTable::deleteByFilter(
							array(
								'OWNER_ID' => $ownerID,
								'SEMANTIC_ID' => PhaseSemantics::getFinalSemantis()
							)
						);

						DealSumStatisticsTable::synchronize(
							$ownerID,
							array(
								'CREATED_DATE' => $startDate,
								'START_DATE' => $startDate,
								'END_DATE' => $endDate,
								'RESPONSIBLE_ID' => $responsibleID
							),
							PhaseSemantics::getProcessSemantis()
						);
					}
					else
					{
						//Remove finalizing record(s) for avoid possible primary key conflict with "process" semantics.
						//We will insert new finalizing record at the end of this method.
						DealSumStatisticsTable::deleteByFilter(
							array(
								'OWNER_ID' => $ownerID,
								'SEMANTIC_ID' => PhaseSemantics::getFinalSemantis()
							)
						);

						if($startDate->getTimestamp() === $endDate->getTimestamp())
						{
							//Deal is finalized and start date is equal to end date. Clear process item and synchronize final item.
							DealSumStatisticsTable::deleteByFilter(
								array(
									'OWNER_ID' => $ownerID,
									'SEMANTIC_ID' => PhaseSemantics::getProcessSemantis()
								)
							);
						}
						else
						{
							//Deal is finalized and start date is not equal to end date. synchronize process and final items.
							DealSumStatisticsTable::synchronize(
								$ownerID,
								array(
									'CREATED_DATE' => $startDate,
									'START_DATE' => $startDate,
									'END_DATE' => $endDate,
									'RESPONSIBLE_ID' => $responsibleID
								),
								PhaseSemantics::getProcessSemantis()
							);
						}
					}
				}
				elseif($stageID !== $latest['STAGE_ID'] && !$isFinalized)
				{
					//Remove finalizing record(s)
					DealSumStatisticsTable::deleteByFilter(
						array(
							'OWNER_ID' => $ownerID,
							'SEMANTIC_ID' => PhaseSemantics::getFinalSemantis()
						)
					);
				}
			}
		}

		$data = array_merge(
			array(
				'OWNER_ID' => $ownerID,
				'CREATED_DATE' => $date,
				'START_DATE' => $startDate,
				'END_DATE' => $endDate,
				'PERIOD_YEAR' => $year,
				'PERIOD_QUARTER' => $quarter,
				'PERIOD_MONTH' => $month,
				'PERIOD_DAY' => $day,
				'RESPONSIBLE_ID' => $responsibleID,
				'CATEGORY_ID' => $categoryID,
				'STAGE_SEMANTIC_ID' => $semanticID,
				'STAGE_ID' => $stageID,
				'IS_LOST' => $isLost ? 'Y' : 'N',
				'CURRENCY_ID' => $accountingCurrencyID,
				'SUM_TOTAL' => $total
			),
			$sumSlots
		);
		DealSumStatisticsTable::upsert($data);
	}
	/**
	 * Unregister Entity
	 * @param int $ownerID Owner ID.
	 * @return void
	 */
	public static function unregister($ownerID)
	{
		DealSumStatisticsTable::deleteByOwner($ownerID);
	}
	/**
	 * Get binding infos
	 * @return array
	 */
	public static function getBindingInfos()
	{
		global $USER_FIELD_MANAGER;
		$fieldInfos = array();
		$userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmDeal::GetUserFieldEntityID());
		$userType->PrepareFieldsInfo($fieldInfos);
		$result = array();
		$bindings = self::getCurrent()->getSlotBindingMap()->getAll();
		foreach($bindings as $binding)
		{
			/** @var StatisticFieldBinding $binding */
			$slotName = $binding->getSlotName();
			if($slotName === 'SUM_TOTAL')
			{
				continue;
			}

			$fieldName = $binding->getFieldName();
			$fieldTitle = isset($fieldInfos[$fieldName]) ? $fieldInfos[$fieldName]['LABELS']['FORM'] : $fieldName;

			$result[] = array(
				'SLOT_NAME' => $slotName,
				'FEILD_NAME' => $fieldName,
				'TITLE' => $fieldTitle
			);
		}
		return $result;
	}
	/**
	 * Get current instance
	 * @return DealSumStatisticEntry
	 * */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new DealSumStatisticEntry();
		}
		return self::$current;
	}
	public static function processCagegoryChange($ownerID)
	{
		self::unregister($ownerID);
		self::register($ownerID);
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
	//region StatisticEntry
	/**
	* Get entity type ID
	* @return string
	*/
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Deal;
	}
	/**
	* Get entry type name
	* @return string
	*/
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/**
	* Check if entry is valid
	* @return boolean
	*/
	public function isValid()
	{
		return Main\Config\Option::get('crm', '~CRM_REBUILD_DEAL_SUM_STATISTICS', 'N', false) !== 'Y';
	}
	/**
	* Get slots data
	* @return array
	*/
	public function getSlotInfos()
	{
		self::includeModuleFile();
		$result = array(
			array(
				'NAME' => 'SUM_TOTAL',
				'IS_FIXED' => true,
				'TITLE' => GetMessage('CRM_DEAL_SUM_STAT_ENTRY_SLOT_SUM_TOTAL')
			)
		);
		$names = DealSumStatisticsTable::getSumSlotFieldNames();
		foreach($names as $name)
		{
			$result[] = array('NAME' => $name);
		}
		return $result;
	}
	/**
	* Get fields data
	* @param string $langID Language ID.
	* @return array
	*/
	public function getSlotFieldInfos($langID = '')
	{
		$infos = array();
		$fields = \CCrmDeal::GetUserFields($langID !== '' ? $langID : LANGUAGE_ID);
		foreach($fields as $field)
		{
			if($field['USER_TYPE_ID'] === 'double' || $field['USER_TYPE_ID'] === 'integer')
			{
				$infos[] = array(
					'NAME' => $field['FIELD_NAME'],
					'TITLE' => isset($field['EDIT_FORM_LABEL']) ? $field['EDIT_FORM_LABEL'] : $field['FIELD_NAME']
				);
			}
		}
		return $infos;
	}
	/**
	* Set binding map
	* @param StatisticFieldBindingMap $srcBindingMap Binding map.
	* @return void
	*/
	public function setSlotBindingMap(StatisticFieldBindingMap $srcBindingMap)
	{
		self::setupSlotBindingMap(self::TYPE_NAME, $srcBindingMap);
		Main\Config\Option::set('crm', '~CRM_REBUILD_DEAL_SUM_STATISTICS', 'Y');
	}
	/**
	* Prepare binging data
	* @return array
	*/
	public function prepareSlotBingingData()
	{
		self::includeModuleFile();
		return array(
			'ID' => self::TYPE_NAME,
			'TITLE' => GetMessage('CRM_DEAL_SUM_STAT_ENTRY_TITLE'),
			'SLOTS' => $this->getSlotInfos(),
			'SLOT_FIELDS' => $this->getSlotFieldInfos(LANGUAGE_ID),
			'SLOT_BINDINGS' => $this->getSlotBindingMap()->toArray(),
			'BUILDER' => $this->prepareBuilderData()
		);
	}
	/**
	* Prepare builder data
	* @return array
	*/
	public function prepareBuilderData()
	{
		self::includeModuleFile();
		return array(
			'ID' => self::TYPE_NAME,
			'ACTIVE' => !$this->isValid(),
			'MESSAGE' => GetMessage('CRM_DEAL_SUM_STAT_ENTRY_REBUILD'),
			'SETTINGS' => array(
				'TITLE' => GetMessage('CRM_DEAL_SUM_STAT_ENTRY_REBUILD_DLG_TITLE'),
				'SUMMARY' => GetMessage('CRM_DEAL_SUM_STAT_ENTRY_REBUILD_DLG_SUMMARY'),
				'ACTION' => 'REBUILD_SUM_STATISTICS',
				'URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()
			)
		);
	}
	/**
	* Get binding map
	* @return StatisticFieldBindingMap
	*/
	public function getSlotBindingMap()
	{
		return self::ensureSlotBindingMapCreated(self::TYPE_NAME);
	}
	/**
	* Get count of busy slots
	* @return integer
	*/
	public function getBusySlotCount()
	{
		return self::calculateBusySlots(self::TYPE_NAME);
	}
	//endregion
}