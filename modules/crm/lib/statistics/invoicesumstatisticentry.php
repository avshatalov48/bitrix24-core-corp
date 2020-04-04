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
use Bitrix\Crm\Statistics\Entity\InvoiceSumStatisticsTable;

class InvoiceSumStatisticEntry
	extends StatisticEntryBase
	implements StatisticEntry
{
	const TYPE_NAME = 'INVOICE_SUM_STATS';
	/** @var InvoiceSumStatisticEntry|null  */
	private static $current = null;
	private static $messagesLoaded = false;
	/**
	 * Get all.
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

		$query = new Query(InvoiceSumStatisticsTable::getEntity());
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
	 * Get latest.
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

		$subQuery = new Query(InvoiceSumStatisticsTable::getEntity());
		$subQuery->registerRuntimeField('', new ExpressionField('MAX_CREATED_DATE', 'MAX(CREATED_DATE)'));
		$subQuery->addSelect('MAX_CREATED_DATE');
		$subQuery->addFilter('=OWNER_ID', $ownerID);
		$subQuery->addGroup('OWNER_ID');

		$query = new Query(InvoiceSumStatisticsTable::getEntity());
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
	 * Check if entity is registered.
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

		$query = new Query(InvoiceSumStatisticsTable::getEntity());
		$query->addSelect('CREATED_DATE');
		$query->addFilter('=OWNER_ID', $ownerID);
		$query->setLimit(1);

		$dbResult = $query->exec();
		$result = $dbResult->fetch();
		return is_array($result);
	}

	/**
	 * Register Entity.
	 * @param int $ownerID Owner ID.
	 * @param array|null $entityFields Entity fields.
	 * @param array|null $options Operation options.
	 * @return void
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentTypeException
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
			$dbResult = \CCrmInvoice::GetList(
				array(),
				array('=ID' => $ownerID, 'CHECK_PERMISSIONS' => 'N')
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

		/** @var Date $date */
		$date = self::resolveEntityCreationDate($entityFields);
		$statusID = isset($entityFields['STATUS_ID']) ? $entityFields['STATUS_ID'] : '';
		$semanticID = \CCrmInvoice::GetSemanticID($statusID);
		$isJunk = PhaseSemantics::isLost($semanticID);
		$responsibleID = isset($entityFields['RESPONSIBLE_ID']) ? (int)$entityFields['RESPONSIBLE_ID'] : 0;
		$companyID = isset($entityFields['UF_COMPANY_ID']) ? (int)$entityFields['UF_COMPANY_ID'] : 0;
		$contactID = isset($entityFields['UF_CONTACT_ID']) ? (int)$entityFields['UF_CONTACT_ID'] : 0;
		/** @var Date $billDate */
		$billDate = self::resolveEntityBillDate($entityFields, $date);
		/** @var Date $payBeforeDate */
		$payBeforeDate = self::resolveEntityPayBeforeDate($entityFields);
		/** @var Date $paidDate */
		$paidDate = self::resolveEntityPaidDate($entityFields);
		$isPaidInTime = self::resolveEntityPaidInTime($entityFields);
		/** @var Date $closedDate */
		$closedDate = self::resolveEntityClosedDate($entityFields, $semanticID, $date);

		//$day = (int)$date->format('d');
		//$month = (int)$date->format('m');
		//$quarter = $month <= 3 ? 1 : ($month <= 6 ? 2 : ($month <= 9 ? 3 : 4));
		//$year = (int)$date->format('Y');

		$currencyID = isset($entityFields['CURRENCY']) ? $entityFields['CURRENCY'] : '';
		$accountingCurrencyID = \CCrmCurrency::GetAccountCurrencyID();
		$sum = isset($entityFields['PRICE']) ? (double)$entityFields['PRICE'] : 0.0;

		$bindingMap = self::getCurrent()->getSlotBindingMap();
		$binding = $bindingMap->get('SUM_TOTAL');
		if($binding === null)
		{
			$total = isset($entityFields['PRICE']) ? (double)$entityFields['PRICE'] : 0.0;
		}
		else
		{
			$bindingFieldName = $binding->getFieldName();
			if($bindingFieldName === '')
			{
				$bindingFieldName = 'PRICE';
			}
			$total = isset($entityFields[$bindingFieldName]) ? (double)$entityFields[$bindingFieldName] : 0.0;

			if($bindingFieldName !== 'PRICE' && $binding->getOption('ADD_PRODUCT_ROW_SUM') === 'Y')
			{
				$total += $sum;
			}
		}

		if($currencyID !== $accountingCurrencyID)
		{
			$accData = \CCrmAccountingHelper::PrepareAccountingData(
				array(
					'CURRENCY_ID' => $currencyID,
					'SUM' => $total
				)
			);
			if(is_array($accData))
			{
				$total = (double)$accData['ACCOUNT_SUM'];
			}
		}

		$sumSlots = array();
		$sumSlotFields = InvoiceSumStatisticsTable::getSumSlotFieldNames();
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
						'SUM' => $slotSum
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
				InvoiceSumStatisticsTable::synchronizeSumFields(
					$ownerID,
					array_merge(array('SUM_TOTAL' => $total), $sumSlots)
				);
			}

			$latestResponsibleID = (int)$latest['RESPONSIBLE_ID'];
			$latestCompanyID = (int)$latest['COMPANY_ID'];
			$latestContactID = (int)$latest['CONTACT_ID'];
			/** @var Date $latestBillDate */
			$latestBillDate = $latest['BILL_DATE'];
			/** @var Date $latestPayBeforeDate */
			$latestPayBeforeDate = $latest['PAY_BEFORE_DATE'];
			/** @var Date $latestPaidDate */
			$latestPaidDate = isset($latest['PAID_DATE']) ? $latest['PAID_DATE'] : null;
			/** @var Date $latestClosedDate */
			$latestClosedDate = isset($latest['CLOSED_DATE']) ? $latest['CLOSED_DATE'] : null;

			if($responsibleID !== $latestResponsibleID
				|| $companyID !== $latestCompanyID
				|| $contactID !== $latestContactID
				|| self::nullSafeCompareDates($payBeforeDate, $latestPayBeforeDate) !== 0
				|| self::nullSafeCompareDates($billDate, $latestBillDate) !== 0
				|| self::nullSafeCompareDates($closedDate, $latestClosedDate) !== 0
				|| self::nullSafeCompareDates($paidDate, $latestPaidDate) !== 0)
			{
				//Update responsible user and dates in all related records
				InvoiceSumStatisticsTable::synchronize(
					$ownerID,
					array(
						'RESPONSIBLE_ID' => $responsibleID,
						'COMPANY_ID' => $companyID,
						'CONTACT_ID' => $contactID,
						'BILL_DATE' => $billDate,
						'PAY_BEFORE_DATE' => $payBeforeDate,
						'PAID_DATE' => $paidDate,
						'IS_PAID_INTIME' => $isPaidInTime,
						'CLOSED_DATE' => $closedDate
					)
				);
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
				'BILL_DATE' => $billDate,
				'PAY_BEFORE_DATE' => $payBeforeDate,
				'PAID_DATE' => $paidDate,
				'IS_PAID_INTIME' => $isPaidInTime,
				'CLOSED_DATE' => $closedDate,
				//'PERIOD_YEAR' => $year,
				//'PERIOD_QUARTER' => $quarter,
				//'PERIOD_MONTH' => $month,
				//'PERIOD_DAY' => $day,
				'RESPONSIBLE_ID' => $responsibleID,
				'COMPANY_ID' => $companyID,
				'CONTACT_ID' => $contactID,
				'STATUS_SEMANTIC_ID' => $semanticID,
				'STATUS_ID' => $statusID,
				'IS_JUNK' => $isJunk ? 'Y' : 'N',
				'CURRENCY_ID' => $accountingCurrencyID,
				'SUM_TOTAL' => $total
			),
			$sumSlots
		);
		InvoiceSumStatisticsTable::upsert($data);
	}
	/**
	 * Unregister Entity.
	 * @param int $ownerID Owner ID.
	 * @return void
	 */
	public static function unregister($ownerID)
	{
		InvoiceSumStatisticsTable::deleteByOwner($ownerID);
	}
	/**
	 * Get binding infos.
	 * @return array
	 */
	public static function getBindingInfos()
	{
		global $USER_FIELD_MANAGER;
		$fieldInfos = array();
		$userType = new \CCrmUserType($USER_FIELD_MANAGER, \CCrmInvoice::GetUserFieldEntityID());
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
	 * Get current instance.
	 * @return InvoiceSumStatisticEntry
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new InvoiceSumStatisticEntry();
		}
		return self::$current;
	}
	/**
	 * Include language file.
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
	/**
	 * Resolve date of creation.
	 * @return Date
	 */
	protected static function resolveEntityCreationDate(array $entityFields, Date $defaultDate = null)
	{
		$date = null;
		if(isset($entityFields['DATE_UPDATE']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_UPDATE']);
		}
		if($date === null && isset($entityFields['DATE_INSERT']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_INSERT']);
		}
		return $date !== null ? $date : ($defaultDate !== null ? $defaultDate : new Date());
	}
	/**
	 * Resolve bill date.
	 * @return Date
	 */
	protected static function resolveEntityBillDate(array $entityFields, Date $defaultDate = null)
	{
		$date = null;
		if(isset($entityFields['DATE_BILL']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_BILL']);
		}
		if($date === null && isset($entityFields['DATE_INSERT']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_INSERT']);
		}
		return $date !== null ? $date : ($defaultDate !== null ? $defaultDate : new Date());
	}
	/**
	 * Resolve before pay date.
	 * @return Date
	 */
	protected static function resolveEntityPayBeforeDate(array $entityFields, Date $defaultDate = null)
	{
		$date = null;
		if(isset($entityFields['DATE_PAY_BEFORE']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_PAY_BEFORE']);
		}
		return $date !== null ? $date : ($defaultDate !== null ? $defaultDate : new Date('9999-12-31', 'Y-m-d'));
	}

	/**
	 * Resolve before pay date.
	 * @param array $entityFields
	 * @param Date $defaultDate
	 * @return Date
	 */
	protected static function resolveEntityPaidDate(array $entityFields, Date $defaultDate = null)
	{
		$date = null;
		if(isset($entityFields['DATE_PAYED']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_PAYED']);
		}
		return $date !== null ? $date : $defaultDate;
	}

	/**
	 * Resolve before pay date.
	 * @param array $entityFields
	 * @return string
	 */
	protected static function resolveEntityPaidInTime(array $entityFields)
	{
		$date = null;
		if(isset($entityFields['DATE_PAY_BEFORE']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_PAY_BEFORE']);
		}
		if ($date !== null)
		{
			$paidDate = null;
			if(isset($entityFields['DATE_PAYED']))
			{
				$paidDate = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_PAYED']);
			}
			if ($paidDate !== null)
			{
				return $paidDate->getTimestamp() <= $date->getTimestamp() ? 'Y' : 'N';
			}
		}
		return 'N';
	}
	/**
	 * Resolve before pay date.
	 * @param array $entityFields Invoice fields.
	 * @param int $semanticID Invoice semantic ID.
	 * @param Date $defaultDate Default date.
	 * @return Date
	 */
	protected static function resolveEntityClosedDate(array $entityFields, $semanticID, Date $defaultDate = null)
	{
		$date = null;
		if($semanticID === PhaseSemantics::SUCCESS && isset($entityFields['PAY_VOUCHER_DATE']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['PAY_VOUCHER_DATE']);
		}
		elseif($semanticID === PhaseSemantics::FAILURE && isset($entityFields['DATE_MARKED']))
		{
			$date = \CCrmDateTimeHelper::ParseDateString($entityFields['DATE_MARKED']);
		}
		return $date !== null ? $date : $defaultDate;
	}
	//region StatisticEntry
	/**
	* Get entity type ID.
	* @return string
	*/
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::Invoice;
	}
	/**
	* Get entry type name.
	* @return string
	*/
	public function getTypeName()
	{
		return self::TYPE_NAME;
	}
	/**
	* Check if entry is valid.
	* @return boolean
	*/
	public function isValid()
	{
		return Main\Config\Option::get('crm', '~CRM_REBUILD_INVOICE_SUM_STATISTICS', 'N', false) !== 'Y';
	}
	/**
	* Get slots data.
	* @return array
	*/
	public function getSlotInfos()
	{
		self::includeModuleFile();
		$result = array(
			array(
				'NAME' => 'SUM_TOTAL',
				'IS_FIXED' => true,
				'TITLE' => GetMessage('CRM_INVOICE_SUM_STAT_ENTRY_SLOT_SUM_TOTAL')
			)
		);
		$names = InvoiceSumStatisticsTable::getSumSlotFieldNames();
		foreach($names as $name)
		{
			$result[] = array('NAME' => $name);
		}
		return $result;
	}
	/**
	* Get fields data.
	* @param string $langID Language ID.
	* @return array
	*/
	public function getSlotFieldInfos($langID = '')
	{
		$infos = array();
		$fields = \CCrmInvoice::GetUserFields($langID !== '' ? $langID : LANGUAGE_ID);
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
	* Set binding map.
	* @param StatisticFieldBindingMap $srcBindingMap Binding map.
	* @return void
	*/
	public function setSlotBindingMap(StatisticFieldBindingMap $srcBindingMap)
	{
		self::setupSlotBindingMap(self::TYPE_NAME, $srcBindingMap);
		Main\Config\Option::set('crm', '~CRM_REBUILD_INVOICE_SUM_STATISTICS', 'Y');
	}
	/**
	* Prepare binging data.
	* @return array
	*/
	public function prepareSlotBingingData()
	{
		self::includeModuleFile();
		return array(
			'ID' => self::TYPE_NAME,
			'TITLE' => GetMessage('CRM_INVOICE_SUM_STAT_ENTRY_TITLE'),
			'SLOTS' => $this->getSlotInfos(),
			'SLOT_FIELDS' => $this->getSlotFieldInfos(LANGUAGE_ID),
			'SLOT_BINDINGS' => $this->getSlotBindingMap()->toArray(),
			'BUILDER' => $this->prepareBuilderData()
		);
	}
	/**
	* Prepare builder data.
	* @return array
	*/
	public function prepareBuilderData()
	{
		self::includeModuleFile();
		return array(
			'ID' => self::TYPE_NAME,
			'ACTIVE' => !$this->isValid(),
			'MESSAGE' => GetMessage('CRM_INVOICE_SUM_STAT_ENTRY_REBUILD'),
			'SETTINGS' => array(
				'TITLE' => GetMessage('CRM_INVOICE_SUM_STAT_ENTRY_REBUILD_DLG_TITLE'),
				'SUMMARY' => GetMessage('CRM_INVOICE_SUM_STAT_ENTRY_REBUILD_DLG_SUMMARY'),
				'ACTION' => 'REBUILD_SUM_STATISTICS',
				'URL' => '/bitrix/components/bitrix/crm.invoice.list/list.ajax.php?'.bitrix_sessid_get()
			)
		);
	}
	/**
	* Get binding map.
	* @return StatisticFieldBindingMap
	*/
	public function getSlotBindingMap()
	{
		return self::ensureSlotBindingMapCreated(self::TYPE_NAME);
	}
	/**
	* Get count of busy slots.
	* @return integer
	*/
	public function getBusySlotCount()
	{
		return self::calculateBusySlots(self::TYPE_NAME);
	}
	//endregion
}