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
use Bitrix\Crm\Statistics\Entity\LeadSumStatisticsTable;

class LeadSumStatisticEntry
	extends StatisticEntryBase
	implements StatisticEntry
{
	const TYPE_NAME = 'LEAD_SUM_STATS';
	/** @var LeadSumStatisticEntry|null  */
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

		$query = new Query(LeadSumStatisticsTable::getEntity());
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

		$subQuery = new Query(LeadSumStatisticsTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(CREATED_DATE)'));
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->addFilter('=OWNER_ID', $ownerID);
		$subQuery->addGroup('OWNER_ID');

		$query = new Query(LeadSumStatisticsTable::getEntity());
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

		$query = new Query(LeadSumStatisticsTable::getEntity());
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
			$dbResult = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'STATUS_ID', 'SOURCE_ID',
					'DATE_CREATE', 'DATE_MODIFY',
					'CURRENCY_ID', 'OPPORTUNITY',
					'ASSIGNED_BY_ID', 'UF_*'
				)
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

		$sourceID = isset($entityFields['SOURCE_ID']) ? $entityFields['SOURCE_ID'] : '';
		$statusID = isset($entityFields['STATUS_ID']) ? $entityFields['STATUS_ID'] : '';
		$semanticID = \CCrmLead::GetSemanticID($statusID);
		$isJunk = PhaseSemantics::isLost($semanticID);
		$responsibleID = isset($entityFields['ASSIGNED_BY_ID']) ? (int)$entityFields['ASSIGNED_BY_ID'] : 0;

		/** @var Date $date */
		$date = self::parseDateString(isset($entityFields['DATE_MODIFY']) ? $entityFields['DATE_MODIFY'] : '');
		if($date === null)
		{
			$date = new Date();
		}

		$day = (int)$date->format('d');
		$month = (int)$date->format('m');
		$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		$year = (int)$date->format('Y');

		$currencyID = isset($entityFields['CURRENCY_ID']) ? $entityFields['CURRENCY_ID'] : '';
		$accountingCurrencyID = \CCrmCurrency::GetAccountCurrencyID();
		$sum = isset($entityFields['OPPORTUNITY']) ? (double)$entityFields['OPPORTUNITY'] : 0.0;

		$bindingMap = self::getCurrent()->getSlotBindingMap();
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
		$sumSlotFields = LeadSumStatisticsTable::getSumSlotFieldNames();
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
				LeadSumStatisticsTable::synchronizeSumFields(
					$ownerID,
					array_merge(array('SUM_TOTAL' => $total), $sumSlots)
				);
			}

			//Update responsible user in all related records
			if($responsibleID !== (int)$latest['RESPONSIBLE_ID'])
			{
				LeadSumStatisticsTable::synchronize($ownerID, array('RESPONSIBLE_ID' => $responsibleID));
			}

			if($statusID === $latest['STATUS_ID']
				&& $currencyID === $latest['CURRENCY_ID']
				&& $total === (double)$latest['SUM_TOTAL']
				&& $sumSlots['UF_SUM_1'] === (double)$latest['UF_SUM_1']
				&& $sumSlots['UF_SUM_2'] === (double)$latest['UF_SUM_2']
				&& $sumSlots['UF_SUM_3'] === (double)$latest['UF_SUM_3']
				&& $sumSlots['UF_SUM_4'] === (double)$latest['UF_SUM_4']
				&& $sumSlots['UF_SUM_5'] === (double)$latest['UF_SUM_5'])
			{
				return;
			}
		}

		$data = array_merge(
			array(
				'OWNER_ID' => $ownerID,
				'CREATED_DATE' => $date,
				'PERIOD_YEAR' => $year,
				'PERIOD_QUARTER' => $quarter,
				'PERIOD_MONTH' => $month,
				'PERIOD_DAY' => $day,
				'RESPONSIBLE_ID' => $responsibleID,
				'STATUS_SEMANTIC_ID' => $semanticID,
				'STATUS_ID' => $statusID,
				'SOURCE_ID' => $sourceID,
				'IS_JUNK' => $isJunk ? 'Y' : 'N',
				'CURRENCY_ID' => $accountingCurrencyID,
				'SUM_TOTAL' => $total
			),
			$sumSlots
		);
		LeadSumStatisticsTable::upsert($data);
	}
	/**
	 * Unregister Entity
	 * @param int $ownerID Owner ID.
	 * @return void
	 */
	public static function unregister($ownerID)
	{
		LeadSumStatisticsTable::deleteByOwner($ownerID);
	}
	/**
	 * Get binding infos
	 * @return array
	 */
	public static function getBindingInfos()
	{
		global $USER_FIELD_MANAGER;
		$fieldInfos = array();
		$userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmLead::GetUserFieldEntityID());
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
	 * @return LeadSumStatisticEntry
	 * */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new LeadSumStatisticEntry();
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
	//region StatisticEntry
	/**
	* Get entity type ID
	* @return string
	*/
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Lead;
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
		return Main\Config\Option::get('crm', '~CRM_REBUILD_LEAD_SUM_STATISTICS', 'N', false) !== 'Y';
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
				'TITLE' => GetMessage('CRM_LEAD_SUM_STAT_ENTRY_SLOT_SUM_TOTAL')
			)
		);
		$names = LeadSumStatisticsTable::getSumSlotFieldNames();
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
		$fields = \CCrmLead::GetUserFields($langID !== '' ? $langID : LANGUAGE_ID);
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
		Main\Config\Option::set('crm', '~CRM_REBUILD_LEAD_SUM_STATISTICS', 'Y');
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
			'TITLE' => GetMessage('CRM_LEAD_SUM_STAT_ENTRY_TITLE'),
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
			'MESSAGE' => GetMessage('CRM_LEAD_SUM_STAT_ENTRY_REBUILD'),
			'SETTINGS' => array(
				'TITLE' => GetMessage('CRM_LEAD_SUM_STAT_ENTRY_REBUILD_DLG_TITLE'),
				'SUMMARY' => GetMessage('CRM_LEAD_SUM_STAT_ENTRY_REBUILD_DLG_SUMMARY'),
				'ACTION' => 'REBUILD_SUM_STATISTICS',
				'URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get()
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