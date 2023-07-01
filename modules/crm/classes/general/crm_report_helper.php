<?php
if (!CModule::IncludeModule('report'))
	return;

use Bitrix\Main;
use Bitrix\Crm;
use Bitrix\Crm\UtmTable;

class CCrmReportManager
{
	private static $OWNER_INFOS = null;
	private static $REPORT_CURRENCY_ID = null;

	public static function GetReportCurrencyID()
	{
		if(!self::$REPORT_CURRENCY_ID)
		{
			self::$REPORT_CURRENCY_ID = CUserOptions::GetOption('crm', 'report_currency_id', '');
			if(!isset(self::$REPORT_CURRENCY_ID[0]))
			{
				self::$REPORT_CURRENCY_ID = CCrmCurrency::GetBaseCurrencyID();
			}
		}

		return self::$REPORT_CURRENCY_ID;
	}

	public static function SetReportCurrencyID($currencyID)
	{
		$currencyID = strval($currencyID);

		if(!isset($currencyID[0]))
		{
			$currencyID = CCrmCurrency::GetBaseCurrencyID();
		}

		if($currencyID === self::$REPORT_CURRENCY_ID)
		{
			return;
		}

		self::$REPORT_CURRENCY_ID = $currencyID;
		CUserOptions::SetOption('crm', 'report_currency_id', $currencyID);
	}

	private static function createOwnerInfo($ID, $className, $title)
	{
		return array(
			'ID' => $ID,
			'HELPER_CLASS' => $className,
			'TITLE' => $title
		);
	}
	public static function getOwnerInfos()
	{
		if(self::$OWNER_INFOS)
		{
			return self::$OWNER_INFOS;
		}

		IncludeModuleLangFile(__FILE__);

		self::$OWNER_INFOS = array();
		self::$OWNER_INFOS[] = self::createOwnerInfo(
			CCrmReportHelper::getOwnerId(),
			'CCrmReportHelper',
			GetMessage('CRM_REPORT_OWNER_TITLE_'.mb_strtoupper(CCrmReportHelper::getOwnerId()))
		);
		self::$OWNER_INFOS[] = self::createOwnerInfo(
			CCrmProductReportHelper::getOwnerId(),
			'CCrmProductReportHelper',
			GetMessage('CRM_REPORT_OWNER_TITLE_'.mb_strtoupper(CCrmProductReportHelper::getOwnerId()))
		);
		self::$OWNER_INFOS[] = self::createOwnerInfo(
			CCrmLeadReportHelper::getOwnerId(),
			'CCrmLeadReportHelper',
			GetMessage('CRM_REPORT_OWNER_TITLE_'.mb_strtoupper(CCrmLeadReportHelper::getOwnerId()))
		);
		self::$OWNER_INFOS[] = self::createOwnerInfo(
			CCrmInvoiceReportHelper::getOwnerId(),
			'CCrmInvoiceReportHelper',
			GetMessage('CRM_REPORT_OWNER_TITLE_'.mb_strtoupper(CCrmInvoiceReportHelper::getOwnerId()))
		);
		self::$OWNER_INFOS[] = self::createOwnerInfo(
			CCrmActivityReportHelper::getOwnerId(),
			'CCrmActivityReportHelper',
			GetMessage('CRM_REPORT_OWNER_TITLE_'.mb_strtoupper(CCrmActivityReportHelper::getOwnerId()))
		);
		return self::$OWNER_INFOS;
	}
	public static function getOwnerInfo($ownerID)
	{
		$ownerID = strval($ownerID);
		if($ownerID === '')
		{
			return null;
		}

		$infos = self::getOwnerInfos();
		foreach($infos as $info)
		{
			if($info['ID'] === $ownerID)
			{
				return $info;
			}
		}
		return null;
	}
	public static function getOwnerHelperClassName($ownerID)
	{
		$info = self::getOwnerInfo($ownerID);
		return $info ? $info['HELPER_CLASS'] : '';
	}
	public static function getReportData($reportID)
	{
		$reportID = intval($reportID);
		return $reportID > 0
			? Bitrix\Report\ReportTable::getById($reportID)->fetch():
			null;
	}
}

abstract class CCrmReportHelperBase extends CReportHelper
{
	const UTM_FIELD_POSTFIX = '_VAL';

	protected static $CURRENT_RESULT_ROWS = null;
	protected static $CURRENT_RESULT_ROW = null;
	protected static $PAY_SYSTEMS = array();
	protected static $PERSON_TYPES = null;
	protected static $userFieldMoneyList = null;

	protected static $urlBuilder;

	protected static function prepareUFInfo()
	{
		if (!is_array(self::$arUFId) || count(self::$arUFId) <= 0 || is_array(self::$ufInfo))
			return;

		/** @global CUserTypeManager $USER_FIELD_MANAGER */
		global $USER_FIELD_MANAGER;

		$allowedUserTypes = array('string', 'date', 'datetime', 'enumeration', 'double', 'integer', 'boolean', 'file',
			'employee', 'crm', 'crm_status', 'iblock_element', 'iblock_section', 'money');

		self::$ufInfo = [];
		self::$ufEnumerations = [];
		self::$userFieldMoneyList = [];

		foreach(self::$arUFId as $ufId)
		{
			$arUserFields = $USER_FIELD_MANAGER->GetUserFields($ufId, 0, LANGUAGE_ID);

			// remove invoice reserved fields
			if ($ufId === CCrmInvoice::GetUserFieldEntityID())
				foreach (CCrmInvoice::GetUserFieldsReserved() as $id)
					if (isset($arUserFields[$id]))
						unset($arUserFields[$id]);

			if (is_array($arUserFields) && count($arUserFields) > 0)
			{
				foreach ($arUserFields as $field)
				{
					if (isset($field['FIELD_NAME']) && mb_substr($field['FIELD_NAME'], 0, 3) === 'UF_'
						/*&& (!isset($field['MULTIPLE']) || $field['MULTIPLE'] !== 'Y')*/
						&& isset($field['USER_TYPE_ID']) && in_array($field['USER_TYPE_ID'], $allowedUserTypes, true))
					{
						self::$ufInfo[$ufId][$field['FIELD_NAME']] = $field;

						if ($field['USER_TYPE_ID'] === 'datetime' && $field['MULTIPLE'] !== 'Y')
							self::$ufInfo[$ufId][$field['FIELD_NAME'].self::UF_DATETIME_SHORT_POSTFIX] = $field;

						$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
						if ($field['USER_TYPE_ID'] === 'boolean' && $field['MULTIPLE'] !== 'Y')
							self::$ufInfo[$ufId][$field['FIELD_NAME'].$blPostfix] = $field;

						if ($field['USER_TYPE_ID'] === 'money')
						{
							self::$userFieldMoneyList[] = $field['FIELD_NAME'];
						}
					}
				}
			}
		}
	}

	protected static function getUTMFieldMap()
	{
		return UtmTable::getCodeNames();
	}

	protected static function prepareUFEnumerations($usedUFMap = null)
	{
		$ufInfo = static::getUFInfo();

		if ($usedUFMap !== null && !is_array($usedUFMap))
		{
			$usedUFMap = array();
		}

		if (is_array($ufInfo))
		{
			foreach ($ufInfo as $entityId => $fieldList)
			{
				foreach ($fieldList as $field)
				{
					if (is_array($field) && isset($field['USER_TYPE_ID']) && $field['USER_TYPE_ID'] === 'enumeration'
						&& isset($field['ENTITY_ID']) && strval($field['ENTITY_ID']) <> ''
						&& !isset(self::$ufEnumerations[$field['ENTITY_ID']][$field['FIELD_NAME']])
						&& ($usedUFMap === null || isset($usedUFMap[$field['ENTITY_ID']][$field['FIELD_NAME']]))
						&& is_array($field['USER_TYPE']) && isset($field['USER_TYPE']['CLASS_NAME'])
						&& !empty($field['USER_TYPE']['CLASS_NAME'])
						&& is_callable(array($field['USER_TYPE']['CLASS_NAME'], 'GetList')))
					{
						self::$ufEnumerations[$field['ENTITY_ID']][$field['FIELD_NAME']] = array();
						$rsEnum = call_user_func_array(array($field['USER_TYPE']['CLASS_NAME'], 'GetList'), array($field));
						while($ar = $rsEnum->Fetch())
						{
							self::$ufEnumerations[$field['ENTITY_ID']][$field['FIELD_NAME']][$ar['ID']] = $ar;
						}
					}
				}
			}
		}
	}

	public static function buildSelectTreePopupElelemnt($humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType, $ufInfo = array())
	{
		// replace by static:: when php 5.3 available
		$grcFields = static::getGrcColumns();

		$isUF = false;
		$isMultiple = false;
		if (is_array($ufInfo) && isset($ufInfo['ENTITY_ID']) && isset($ufInfo['FIELD_NAME']))
		{
			if (isset($ufInfo['MULTIPLE']) && $ufInfo['MULTIPLE'] === 'Y')
				$isMultiple = true;
			$isUF = true;
		}

		if ($isUF && $isMultiple
			&& mb_substr($fieldDefinition, -mb_strlen(self::UF_TEXT_TRIM_POSTFIX)) === self::UF_TEXT_TRIM_POSTFIX)
		{
			return '';
		}

		return parent::buildSelectTreePopupElelemnt(
			$humanTitle, $fullHumanTitle, $fieldDefinition, $fieldType, $ufInfo
		);
	}

	public static function appendBooleanUserFieldsIfNull(\Bitrix\Main\Entity\Base $entity)
	{
		/** @var Bitrix\Main\DB\SqlHelper $sqlHelper */
		$sqlHelper = null;

		// Advanced fields for boolean user fields
		$dateFields = array();
		foreach($entity->getFields() as $field)
		{
			if (in_array($field->getName(), array('LEAD_BY', 'COMPANY_BY', 'CONTACT_BY'), true) && $field instanceof Bitrix\Main\Entity\ReferenceField)
			{
				self::appendBooleanUserFieldsIfNull($field->getRefEntity());
			}
			else if ($field instanceof Bitrix\Main\Entity\ExpressionField)
			{
				$arUF = self::detectUserField($field);
				if ($arUF['isUF'])
				{
					$ufDataType = self::getUserFieldDataType($arUF);
					if ($ufDataType === 'boolean' && $arUF['ufInfo']['MULTIPLE'] !== 'Y')
					{
						if ($sqlHelper === null)
						{
							$sqlHelper = Main\Application::getConnection()->getSqlHelper();
						}

						$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
						$dateFields[] = array(
							'def' => array(
								'data_type' => 'boolean',
								'expression' => array(
									$sqlHelper->getIsNullFunction('%s', 0), $arUF['ufInfo']['FIELD_NAME']
								)
							),
							'name' => $arUF['ufInfo']['FIELD_NAME'].$blPostfix
						);
					}
				}
			}
		}
		foreach ($dateFields as $fieldInfo)
		{
			if (!$entity->hasField($fieldInfo['name']))
			{
				$entity->addField($fieldInfo['def'], $fieldInfo['name']);
			}
		}
	}

	public static function appendDateTimeUserFieldsAsShort(\Bitrix\Main\Entity\Base $entity)
	{
		/** @global CDatabase $DB */
		global $DB;

		// Advanced fields for datetime user fields
		$dateFields = array();
		foreach($entity->getFields() as $field)
		{
			if (in_array($field->getName(), array('LEAD_BY', 'COMPANY_BY', 'CONTACT_BY'), true) && $field instanceof Bitrix\Main\Entity\ReferenceField)
			{
				self::appendDateTimeUserFieldsAsShort($field->getRefEntity());
			}
			else if ($field instanceof Bitrix\Main\Entity\ExpressionField)
			{
				$arUF = self::detectUserField($field);
				if ($arUF['isUF'])
				{
					$ufDataType = self::getUserFieldDataType($arUF);
					if ($ufDataType === 'datetime' && $arUF['ufInfo']['MULTIPLE'] !== 'Y')
					{
						$dateFields[] = array(
							'def' => array(
								'data_type' => 'datetime',
								'expression' => array(
									$DB->DatetimeToDateFunction('%s'), $arUF['ufInfo']['FIELD_NAME']
								)
							),
							'name' => $arUF['ufInfo']['FIELD_NAME'].self::UF_DATETIME_SHORT_POSTFIX
						);
					}
				}
			}
		}
		foreach ($dateFields as $fieldInfo)
		{
			if (!$entity->hasField($fieldInfo['name']))
			{
				$entity->addField($fieldInfo['def'], $fieldInfo['name']);
			}
		}
	}

	public static function appendMoneyUserFieldsAsSeparated(\Bitrix\Main\Entity\Base $entity)
	{
		/** @global CDatabase $DB */
		global $DB;

		// Advanced fields for datetime user fields
		$moneyFields = array();
		foreach($entity->getFields() as $field)
		{
			if (in_array($field->getName(), array('LEAD_BY', 'COMPANY_BY', 'CONTACT_BY'), true)
				&& $field instanceof Bitrix\Main\Entity\ReferenceField)
			{
				self::appendMoneyUserFieldsAsSeparated($field->getRefEntity());
			}
			else if ($field instanceof Bitrix\Main\Entity\ExpressionField)
			{
				$arUF = self::detectUserField($field);
				if ($arUF['isUF'])
				{
					$ufDataType = self::getUserFieldDataType($arUF);
					$fieldName = $arUF['ufInfo']['FIELD_NAME'];
					if ($ufDataType === 'money')
					{
						$moneyFields[] = array(
							'def' => array(
								'data_type' => 'float',
								'expression' => array(
									"(IFNULL(IF(".
									"LOCATE('|', %s) > 0, ".
									"CAST(SUBSTR(%s, 1, LOCATE('|', %s) - 1) AS DECIMAL(18,2)), ".
									"CAST(%s AS DECIMAL(18,2))".
									"), 0))", $fieldName, $fieldName, $fieldName, $fieldName
								)
							),
							'name' => $fieldName.self::UF_MONEY_NUMBER_POSTFIX
						);
						$moneyFields[] = array(
							'def' => array(
								'data_type' => 'string',
								'expression' => array(
									"(IFNULL(IF(LOCATE('|', %s) > 0, SUBSTR(%s, LOCATE('|', %s) + 1), NULL), ''))",
									$fieldName, $fieldName, $fieldName
								)
							),
							'name' => $fieldName.self::UF_MONEY_CURRENCY_POSTFIX
						);
					}
				}
			}
		}
		foreach ($moneyFields as $fieldInfo)
		{
			if (!$entity->hasField($fieldInfo['name']))
			{
				$entity->addField($fieldInfo['def'], $fieldInfo['name']);
			}
		}
	}

	public static function appendTextUserFieldsAsTrimmed(\Bitrix\Main\Entity\Base $entity)
	{
		foreach($entity->getFields() as $field)
		{
			if (in_array($field->getName(), array('LEAD_BY', 'COMPANY_BY', 'CONTACT_BY'), true) && $field instanceof Bitrix\Main\Entity\ReferenceField)
			{
				self::appendTextUserFieldsAsTrimmed($field->getRefEntity());
			}
		}
	}

	public static function appendUTMFields(\Bitrix\Main\Entity\Base $entity)
	{
		// Lead, Deal, Invoice->Deal, Product->Deal

		$connection = Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$entityTypeId = CCrmOwnerType::ResolveID(mb_strtoupper($entity->getName()));
		$utmRefMap = static::getUTMFieldMap();
		$isNeedAppendFields = ($entityTypeId === CCrmOwnerType::Lead || $entityTypeId === CCrmOwnerType::Deal)
			&& is_array($utmRefMap) && !empty($utmRefMap);
		$utmFields = [];

		foreach($entity->getFields() as $field)
		{
			if (in_array($field->getName(), array('INVOICE_UTS', 'DEAL_BY', 'DEAL_OWNER'), true)
				&& $field instanceof Bitrix\Main\Entity\ReferenceField)
			{
				self::appendUTMFields($field->getRefEntity());
			}
			else
			{
				$fieldName = $field->getName();
				if ($isNeedAppendFields && $field instanceof Bitrix\Main\Entity\ReferenceField
					&& isset($utmRefMap[$fieldName]))
				{
					$utmFields[] = [
						'def' => [
							'data_type' => 'string',
							'expression' => [
								"(SELECT UTM.VALUE ".
								"FROM b_crm_utm UTM ".
								"WHERE UTM.ENTITY_TYPE_ID = $entityTypeId ".
								"AND UTM.ENTITY_ID = %s ".
								"AND UTM.CODE = '".$sqlHelper->forSql($fieldName, 45)."')",
								'ID'
							]
						],
						'name' => $fieldName.self::UTM_FIELD_POSTFIX
					];
				}
				else if ($fieldName === 'IS_RETURN_CUSTOMER')
				{
					$utmFields[] = [
						'def' => [
							'data_type' => 'boolean',
							'expression' => ['%s', 'IS_RETURN_CUSTOMER'],
							'values' => array('N', 'Y')
						],
						'name' => 'IS_RETURN_CUSTOMER_BL'
					];
				}
			}
		}

		foreach ($utmFields as $fieldInfo)
		{
			if (!$entity->hasField($fieldInfo['name']))
			{
				$entity->addField($fieldInfo['def'], $fieldInfo['name']);
			}
		}
	}

	public static function getAlternatePhrasesOfColumns()
	{
		$result = parent::getAlternatePhrasesOfColumns();

		if (!is_array($result))
		{
			$result = [];
		}

		foreach (static::getUTMFieldMap() as $fieldId => $fieldTitle)
		{
			foreach (['LEAD', 'DEAL'] as $entityName)
			{
				$phraseKey = 'CRM_'.$entityName.'_ENTITY_'.$fieldId.self::UTM_FIELD_POSTFIX.'_FIELD';
				$result[$phraseKey] = $fieldTitle;
			}
		}

		return $result;
	}

	public static function getFDMsMultipleTrimmed()
	{
		return array(
			array(__CLASS__, 'fdmMultipleTrimmed')
		);
	}

	public static function getFDMsMultipleTrimmedDateTime()
	{
		return array(
			array(__CLASS__, 'fdmMultipleTrimmed'),
			array(__CLASS__, 'fdmMultipleTrimmedDateTime')
		);
	}

	public static function fdmMultipleTrimmed($value, $query, $dataRow, $columnAlias)
	{
		$result = @unserialize($value, ['allowed_classes' => false]);

		return $result;
	}

	public static function fdmMultipleTrimmedDateTime($value, $query, $dataRow, $columnAlias)
	{
		$result = array();

		if (is_array($value))
		{
			foreach ($value as $v)
			{
				if (!empty($v))
				{
					try
					{
						//try new independent datetime format
						$v = new Bitrix\Main\Type\DateTime($v, \Bitrix\Main\UserFieldTable::MULTIPLE_DATETIME_FORMAT);
					}
					catch (Main\ObjectException $e)
					{
						//try site format
						try
						{
							$v = new Bitrix\Main\Type\DateTime($v);
						}
						catch (Main\ObjectException $e)
						{
							//try short format
							$v = Bitrix\Main\Type\DateTime::createFromUserTime($v);
						}
					}
					$result[] = $v;
				}
			}
		}

		return $result;
	}

	public static function getCurrentVersion()
	{
		global $arModuleVersion;

		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/crm/install/version.php");
		return $arModuleVersion['VERSION'];
	}
	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options, $runtime);
		self::rewriteMoneyFilter($filter, $runtime);
	}
	protected static function rewriteMoneyFilter(&$filter, &$runtime)
	{
		static $operationCodes = null;
		static $moneyFieldRegExp = null;
		static $sqlHelper = null;
		static $allowedOperations = null;

		if ($operationCodes === null)
		{
			$operationCodes = array_flip(CReport::$iBlockCompareVariations);
		}

		if ($sqlHelper === null)
		{
			$sqlHelper = Main\Application::getConnection()->getSqlHelper();
		}

		if ($allowedOperations === null)
		{
			$allowedOperations = ['EQUAL', 'GREATER', 'LESS', 'NOT_EQUAL', 'GREATER_OR_EQUAL', 'LESS_OR_EQUAL'];
		}

		if ($moneyFieldRegExp === null)
		{
			$moneyFieldRegExp = '';

			if (is_array(self::$userFieldMoneyList) && !empty(self::$userFieldMoneyList))
			{
				$moneyFieldRegExp .= '(';
				$number = 0;
				foreach (self::$userFieldMoneyList as $fieldName)
				{
					if ($number++ > 0)
					{
						$moneyFieldRegExp .= '|';
					}
					$moneyFieldRegExp .= preg_quote($fieldName);
				}
				unset($number);
				$moneyFieldRegExp .= ')';
			}
		}

		if ($moneyFieldRegExp !== '')
		{
			$newFilter = [];
			foreach ($filter as $k => &$v)
			{
				$skipFilterElement = false;
				if (is_array($v))
				{
					self::rewriteMoneyFilter($v, $runtime);
				}
				else if ($k !== 'LOGIC' && !is_numeric($k))
				{
					$matches = array();
					if (preg_match('/^(>=|<=|=|>|<|!)?(.*'.$moneyFieldRegExp.')$/', $k, $matches))
					{
						if (is_string($v) && $v !== '')
						{
							if (!isset($matches[1]))
							{
								$matches[1] = '=';
							}
							$operationCode = $operationCodes[$matches[1]];
							$valueParts = explode('|', $v);
							if (is_array($valueParts) && isset($valueParts[0])
								&& is_string($valueParts[0]) && $valueParts[0] !== '')
							{
								$numberFieldName = $matches[2].self::UF_MONEY_NUMBER_POSTFIX;
								$currencyFieldName = $matches[2].self::UF_MONEY_CURRENCY_POSTFIX;
								$numberValue = (double)$valueParts[0];
								$currencyValue = '';
								if (isset($valueParts[1]) && is_string($valueParts[1]) && $valueParts[1] !== '')
								{
									$currencyValue = $valueParts[1];
								}
								if (in_array($operationCode, $allowedOperations, true))
								{
									$filterOperation = CReport::$iBlockCompareVariations[$operationCode];
									if ($currencyValue === '')
									{
										$newFilter[$filterOperation.$numberFieldName] = $numberValue;
									}
									else
									{
										if ($filterOperation === '!')
										{
											$newFilter[] = [
												'LOGIC' => 'OR',
												$filterOperation.$numberFieldName => $numberValue,
												'!'.$currencyFieldName => $currencyValue
											];
										}
										else
										{
											$newFilter[] = [
												'LOGIC' => 'AND',
												$filterOperation.$numberFieldName => $numberValue,
												'='.$currencyFieldName => $currencyValue
											];
										}
									}
								}
							}
						}
						$skipFilterElement = true;
					}
				}
				if (!$skipFilterElement)
				{
					if (is_numeric($k))
					{
						$newFilter[] = $v;
					}
					else
					{
						$newFilter[$k] = $v;
					}
				}
			}
			unset($v);

			$filter = $newFilter;
		}
	}
	public static function fillFilterReferenceColumn(&$filterElement, Main\Entity\ReferenceField $field)
	{
		if (
			$field->getRefEntityName() === '\Bitrix\Crm\Company'
			|| $field->getRefEntityName() === '\Bitrix\Crm\CompanyTable'
		)
		{
			// CrmCompany
			if ($filterElement['value'])
			{
				$entity = CCrmCompany::GetById($filterElement['value']);
				if ($entity)
				{
					$filterElement['value'] = array('id' => $entity['ID'], 'name' => $entity['TITLE']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('CRM_COMPANY_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		elseif (
			$field->getRefEntityName() == '\Bitrix\Crm\Contact'
			|| $field->getRefEntityName() == '\Bitrix\Crm\ContactTable'
		)
		{
			// CrmContact
			if ($filterElement['value'])
			{
				$entity = CCrmContact::GetById($filterElement['value']);
				if ($entity)
				{
					$filterElement['value'] = array('id' => $entity['ID'], 'name' => $entity['FULL_NAME']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('CRM_CONTACT_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		elseif ($field->getRefEntityName() == '\Bitrix\Crm\Invoice')
		{
			// CrmInvoice
			if ($filterElement['value'])
			{
				$entity = CCrmInvoice::GetById($filterElement['value']);
				if ($entity)
				{
					$filterElement['value'] = array('id' => $entity['ID'], 'name' => $entity['ORDER_TOPIC']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('CRM_INVOICE_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		elseif ($field->getRefEntityName() == '\Bitrix\Crm\Deal')
		{
			// CrmDeal
			if ($filterElement['value'])
			{
				$entity = CCrmDeal::GetById($filterElement['value']);
				if ($entity)
				{
					$filterElement['value'] = array('id' => $entity['ID'], 'name' => $entity['TITLE']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('CRM_DEAL_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		elseif ($field->getRefEntityName() == '\Bitrix\Crm\Lead')
		{
			// CrmLead
			if ($filterElement['value'])
			{
				$entity = CCrmLead::GetById($filterElement['value']);
				if ($entity)
				{
					$filterElement['value'] = array('id' => $entity['ID'], 'name' => $entity['TITLE']);
				}
				else
				{
					$filterElement['value'] = array('id' => $filterElement['value'], 'name' => GetMessage('CRM_LEAD_NOT_FOUND'));
				}
			}
			else
			{
				$filterElement['value'] = array('id' => '');
			}
		}
		parent::fillFilterReferenceColumn($filterElement, $field);
	}
	public static function formatResults(&$rows, &$columnInfo, $total, &$customChartData = null)
	{
		self::$CURRENT_RESULT_ROWS = $rows;
		foreach ($rows as $rowNum => &$row)
		{
			self::$CURRENT_RESULT_ROW = $row;
			foreach ($row as $k => &$v)
			{
				if (!array_key_exists($k, $columnInfo))
				{
					continue;
				}

				$cInfo = $columnInfo[$k];

				if (is_array($v))
				{
					foreach ($v as $subk => &$subv)
					{
						$customChartValue = is_null($customChartData) ? null : array();
						static::formatResultValue($k, $subv, $row, $cInfo, $total, $customChartValue);
						if (is_array($customChartValue)
							&& isset($customChartValue['exist']) && $customChartValue['exist'] = true)
						{
							if (!isset($customChartData[$rowNum]))
								$customChartData[$rowNum] = array();
							if (!isset($customChartData[$rowNum][$k]))
								$customChartData[$rowNum][$k] = array();
							$customChartData[$rowNum][$k]['multiple'] = true;
							if (!isset($customChartData[$rowNum][$k][$subk]))
								$customChartData[$rowNum][$k][$subk] = array();
							$customChartData[$rowNum][$k][$subk]['type'] = $customChartValue['type'];
							$customChartData[$rowNum][$k][$subk]['value'] = $customChartValue['value'];
						}
					}
				}
				else
				{
					$customChartValue = is_null($customChartData) ? null : array();
					static::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
					if (is_array($customChartValue)
						&& isset($customChartValue['exist']) && $customChartValue['exist'] = true)
					{
						if (!isset($customChartData[$rowNum]))
							$customChartData[$rowNum] = array();
						if (!isset($customChartData[$rowNum][$k]))
							$customChartData[$rowNum][$k] = array();
						$customChartData[$rowNum][$k]['multiple'] = false;
						if (!isset($customChartData[$rowNum][$k][0]))
							$customChartData[$rowNum][$k][0] = array();
						$customChartData[$rowNum][$k][0]['type'] = $customChartValue['type'];
						$customChartData[$rowNum][$k][0]['value'] = $customChartValue['value'];
					}
				}
			}
		}

		unset($row, $v, $subv);
		self::$CURRENT_RESULT_ROWS = self::$CURRENT_RESULT_ROW = null;
	}
	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		// HACK: detect if 'report.view' component is rendering excel spreadsheet
		$isHtml = !(isset($_GET['EXCEL']) && $_GET['EXCEL'] === 'Y');

		foreach($total as $k => &$v)
		{
			if (preg_match('/_QUANTITY$/', $k))
			{
				$v = round(doubleval($v), 4);
			}
			elseif(preg_match('/_OPPORTUNITY$/', $k)
				|| preg_match('/_ACCOUNT$/', $k)
				|| preg_match('/_AMOUNT$/', $k)
				|| preg_match('/_PRICE$/', $k)
				|| preg_match('/_PRICE_WORK$/', $k)
				|| preg_match('/_PRICE_PAYED$/', $k)
				|| preg_match('/_PRICE_CANCELED$/', $k))
			{
				$v = self::MoneyToString(doubleval($v), $isHtml);
			}
		}

		parent::formatResultsTotal($total, $columnInfo);
	}
	protected static function MoneyToString($sum, $isHtml = true)
	{
		$result = CCrmCurrency::MoneyToString($sum, CCrmCurrency::GetAccountCurrencyID(), '#');

		if ($isHtml)
			$result = str_replace(' ', '&nbsp;', $result);

		return $result;
	}
	protected static function prepareDealTitleHtml($dealID, $title)
	{
		$url = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Deal, $dealID, false);
		return '<a target="_blank" href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($title).'</a>';
	}
	protected static function prepareLeadTitleHtml($leadID, $title)
	{
		$url = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Lead, $leadID, false);
		return '<a target="_blank" href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($title).'</a>';
	}
	protected static function prepareInvoiceTitleHtml($invoiceID, $title)
	{
		$url = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Invoice, $invoiceID, false);
		return '<a target="_blank" href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($title).'</a>';
	}
	protected static function getDealCategoryName($categoryID, $htmlEncode = false)
	{
		$name = Bitrix\Crm\Category\DealCategory::getName($categoryID);
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getStatusName($code, $type, $htmlEncode = false)
	{
		$code = strval($code);
		$type = strval($type);
		if($code === '' || $type === '')
		{
			return '';
		}

		$statuses = CCrmStatus::GetStatus($type);
		$name = array_key_exists($code, $statuses) ? $statuses[$code]['NAME'] : $code;
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getWebFormName($id, $htmlEncode = false)
	{
		$webFormNames = Crm\WebForm\Manager::getListNames();
		$name = isset($webFormNames[$id]) ? $webFormNames[$id] : '';

		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getDealStageName($code, $htmlEncode = false)
	{
		$name = Bitrix\Crm\Category\DealCategory::getStageName($code);
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getInvoiceStatusName($code, $htmlEncode = false)
	{
		return self::getStatusName($code, 'INVOICE_STATUS', $htmlEncode);
	}
	protected static function ensurePaySystemsLoaded()
	{
		$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
		foreach ($arPersonTypes as $personTypeId)
		{
			$paySystems = CCrmPaySystem::GetPaySystemsListItems($personTypeId);
			if (is_array($paySystems))
				self::$PAY_SYSTEMS[$personTypeId] = $paySystems;
		}
	}
	public static function getInvoicePaySystemList($htmlEncode = false)
	{
		self::ensurePaySystemsLoaded();
		$arPaySystems = self::$PAY_SYSTEMS;
		$paySystemList = array();
		foreach ($arPaySystems as $arElement)
		{
			foreach ($arElement as $paySystemId => $paySystemName)
			{
				if (!isset($paySystemList[$paySystemId]))
					$paySystemList[$paySystemId] = $htmlEncode ? htmlspecialcharsbx($paySystemName) : $paySystemName;;
			}
		}
		return $paySystemList;
	}
	protected static function getInvoicePaySystemName($code, $personTypeId = 0, $htmlEncode = false)
	{
		self::ensurePaySystemsLoaded();

		$personTypeId = (int)$personTypeId;
		$name = '';
		foreach (self::$PAY_SYSTEMS as $key => $paySystems)
		{
			if ($personTypeId > 0 && $personTypeId !== $key)
			{
				continue;
			}

			if (isset($paySystems[$code]))
			{
				$name = ($paySystems[$code] <> '') ? $paySystems[$code] : $code;
				break;
			}
		}

		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function ensurePersonTypesLoaded()
	{
		if (is_null(self::$PERSON_TYPES))
		{
			self::$PERSON_TYPES = array_flip(CCrmPaySystem::getPersonTypeIDs());
		}
	}
	public static function getInvoicePersonTypeList($htmlEncode = false)
	{
		self::ensurePersonTypesLoaded();
		$arPersonType = self::$PERSON_TYPES;
		foreach ($arPersonType as $k => $name)
		{
			if ($name === 'CONTACT' || $name === 'COMPANY')
			{
				$newName = GetMessage('CRM_PERSON_TYPE_'.$name);
				$arPersonType[$k] = $htmlEncode ? htmlspecialcharsbx($newName) : $newName;
			}
		}
		return $arPersonType;
	}
	protected static function getInvoicePersonTypeName($code, $htmlEncode = false)
	{
		self::ensurePersonTypesLoaded();
		$arPersonType = self::$PERSON_TYPES;
		$name = (isset($arPersonType[$code]) && $arPersonType[$code] <> '') ? $arPersonType[$code] : $code;
		if ($name === 'CONTACT' || $name === 'COMPANY')
			$name = GetMessage('CRM_PERSON_TYPE_'.$name);
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getActivityTypeName($code, $htmlEncode = false)
	{
		$name = CCrmActivityType::ResolveDescription($code);
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getActivityDirectionName($code, $typeID, $htmlEncode = false)
	{
		$name = CCrmActivityDirection::ResolveDescription($code, $typeID);
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getActivityPriorityName($code, $htmlEncode = false)
	{
		$name = CCrmActivityPriority::ResolveDescription($code);
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getLeadStatusName($code, $htmlEncode = false)
	{
		return self::getStatusName($code, 'STATUS', $htmlEncode);
	}
	protected static function getLeadSourceName($code, $htmlEncode = false)
	{
		return self::getStatusName($code, 'SOURCE', $htmlEncode);
	}
	protected static function getDealTypeName($code, $htmlEncode = false)
	{
		return self::getStatusName($code, 'DEAL_TYPE', $htmlEncode);
	}
	protected static function getEventTypeName($code, $htmlEncode = false)
	{
		return self::getStatusName($code, 'EVENT_TYPE', $htmlEncode);
	}
	protected static function getCurrencyName($ID, $htmlEncode = false)
	{
		$currency = CCrmCurrency::GetByID($ID);
		if($currency)
		{
			return $currency['FULL_NAME'];
		}

		// Old style (for compatibility only)
		$statuses =  CCrmStatus::GetStatus('CURRENCY');
		$name = array_key_exists($ID, $statuses) ? $statuses[$ID]['NAME'] : $ID;
		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function getDealOriginatorName($originatorID, $htmlEncode = false)
	{
		$rsSaleSttings = CCrmExternalSale::GetList(array(), array('ID' => intval($originatorID)));
		$arSaleSettings = $rsSaleSttings->Fetch();
		if(!is_array($arSaleSettings))
		{
			return $originatorID;
		}

		$name = isset($arSaleSettings['NAME']) ? strval($arSaleSettings['NAME']) : '';
		if($name === '')
		{
			$name = isset($arSaleSettings['SERVER']) ? strval($arSaleSettings['SERVER']) : '';
		}

		return $htmlEncode ? htmlspecialcharsbx($name) : $name;
	}
	protected static function prepareCompanyTitleHtml($companyID, $title)
	{
		$url = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Company, $companyID, false);
		return '<a target="_blank" href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($title).'</a>';
	}
	protected static function prepareContactTitleHtml($contactID, $title)
	{
		$url = \CCrmOwnerType::GetEntityShowPath(\CCrmOwnerType::Contact, $contactID, false);
		return '<a target="_blank" href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($title).'</a>';
	}

	protected static function prepareProductNameHtml($productID, $name)
	{
		if (!self::$urlBuilder)
		{
			self::$urlBuilder = new Crm\Product\Url\ProductBuilder();
			self::$urlBuilder->setIblockId(\CCrmCatalog::GetDefaultID());
			self::$urlBuilder->setUrlParams([]);
		}
		$url = self::$urlBuilder->getElementDetailUrl($productID);

		return '<a target="_blank" href="'.htmlspecialcharsbx($url).'">'.htmlspecialcharsbx($name).'</a>';
	}
}

class CCrmReportHelper extends CCrmReportHelperBase
{
	protected static function prepareUFInfo()
	{
		if (is_array(self::$arUFId))
			return;

		self::$arUFId = array('CRM_DEAL', 'CRM_LEAD', 'CRM_CONTACT', 'CRM_COMPANY');
		parent::prepareUFInfo();
	}

	public static function GetReportCurrencyID()
	{
		return CCrmReportManager::GetReportCurrencyID();
	}

	public static function SetReportCurrencyID($currencyID)
	{
		CCrmReportManager::SetReportCurrencyID($currencyID);
	}

	public static function getEntityName()
	{
		return 'Bitrix\Crm\Deal';
	}
	public static function getOwnerId()
	{
		return 'crm';
	}
	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		$columnList = array(
			'ID',
			'TITLE',
			'COMMENTS',
			'CATEGORY_ID',
			'STAGE_ID',
			'STAGE_SUB' => array(
				'IS_WORK',
				'IS_WON',
				'IS_LOSE'
			),
			'CLOSED',
			'TYPE_ID',
			'PROBABILITY',
			'OPPORTUNITY',
			'CURRENCY_ID',
			'OPPORTUNITY_ACCOUNT',
			//'ACCOUNT_CURRENCY_ID', //Is always same for all deals
			'RECEIVED_AMOUNT',
			'LOST_AMOUNT',
			'BEGINDATE_SHORT',
			'CLOSEDATE_SHORT',
			'EVENT_ID',
			'EVENT_DATE_SHORT',
			'EVENT_DESCRIPTION',
			'ASSIGNED_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'DATE_CREATE_SHORT',
			'CREATED_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'DATE_MODIFY_SHORT',
			'MODIFY_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'WEBFORM_ID',
			'IS_RETURN_CUSTOMER_BL'
		);
		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$columnList[] = $fieldId.self::UTM_FIELD_POSTFIX;
		}
		$columnList = array_merge(
			$columnList,
			[
				'LEAD_BY' => array(
					'ID',
					'TITLE',
					'STATUS_BY.STATUS_ID',
					'STATUS_DESCRIPTION',
					'OPPORTUNITY',
					'CURRENCY_ID',
					'COMMENTS',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME',
				'BIRTHDATE',
					'COMPANY_TITLE',
					'POST',
					'ADDRESS',
					'SOURCE_BY.STATUS_ID',
					'SOURCE_DESCRIPTION',
					'DATE_CREATE',
					'DATE_MODIFY',
					'ASSIGNED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'CREATED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'MODIFY_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					)
				),
				'CONTACT_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME',
				'BIRTHDATE',
					'POST',
					'ADDRESS',
					'TYPE_BY.STATUS_ID',
					'COMMENTS',
					'SOURCE_BY.STATUS_ID',
					'SOURCE_DESCRIPTION',
					'DATE_CREATE',
					'DATE_MODIFY',
					'ASSIGNED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'CREATED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'MODIFY_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					)
				),
				'COMPANY_BY' => array(
					'ID',
					'TITLE',
					'COMPANY_TYPE_BY.STATUS_ID',
					'INDUSTRY_BY.STATUS_ID',
					'EMPLOYEES_BY.STATUS_ID',
					'REVENUE',
					'CURRENCY_ID',
					'COMMENTS',
					'ADDRESS',
					'ADDRESS_LEGAL',
					'BANKING_DETAILS',
					'DATE_CREATE',
					'DATE_MODIFY',
					'CREATED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'MODIFY_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					)
				),
				'HAS_PRODUCTS',
				'PRODUCT_ROW' => array(
					'ProductRow:DEAL_OWNER.IBLOCK_ELEMENT.ID',
					'ProductRow:DEAL_OWNER.CP_PRODUCT_NAME',
					'ProductRow:DEAL_OWNER.PRICE_ACCOUNT',
					'ProductRow:DEAL_OWNER.QUANTITY',
					'ProductRow:DEAL_OWNER.SUM_ACCOUNT'
				),
				'ORIGINATOR_BY.ID'
			]
		);

		// Append user fields
		$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
		self::prepareUFInfo();
		if (is_array(self::$ufInfo) && count(self::$ufInfo) > 0)
		{
			if (isset(self::$ufInfo['CRM_DEAL']) && is_array(self::$ufInfo['CRM_DEAL'])
				&& count(self::$ufInfo['CRM_DEAL']) > 0)
			{
				foreach (self::$ufInfo['CRM_DEAL'] as $ufKey => $uf)
				{
					if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
						|| $uf['MULTIPLE'] === 'Y'
						|| mb_substr($ufKey, -mb_strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
						|| mb_substr($ufKey, -mb_strlen($blPostfix)) === $blPostfix)
					{
						$columnList[] = $ufKey;
					}
				}
			}
			if (isset(self::$ufInfo['CRM_LEAD']) && is_array(self::$ufInfo['CRM_LEAD'])
				&& count(self::$ufInfo['CRM_LEAD']) > 0)
			{
				foreach (self::$ufInfo['CRM_LEAD'] as $ufKey => $uf)
				{
					if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
						|| $uf['MULTIPLE'] === 'Y'
						|| mb_substr($ufKey, -mb_strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
						|| mb_substr($ufKey, -mb_strlen($blPostfix)) === $blPostfix)
					{
						$columnList['LEAD_BY'][] = $ufKey;
					}
				}
			}
			if (isset(self::$ufInfo['CRM_CONTACT']) && is_array(self::$ufInfo['CRM_CONTACT'])
				&& count(self::$ufInfo['CRM_CONTACT']) > 0)
			{
				foreach (self::$ufInfo['CRM_CONTACT'] as $ufKey => $uf)
				{
					if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
						|| $uf['MULTIPLE'] === 'Y'
						|| mb_substr($ufKey, -mb_strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
						|| mb_substr($ufKey, -mb_strlen($blPostfix)) === $blPostfix)
					{
						$columnList['CONTACT_BY'][] = $ufKey;
					}
				}
			}
			if (isset(self::$ufInfo['CRM_COMPANY']) && is_array(self::$ufInfo['CRM_COMPANY'])
				&& count(self::$ufInfo['CRM_COMPANY']) > 0)
			{
				foreach (self::$ufInfo['CRM_COMPANY'] as $ufKey => $uf)
				{
					if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
						|| $uf['MULTIPLE'] === 'Y'
						|| mb_substr($ufKey, -mb_strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
						|| mb_substr($ufKey, -mb_strlen($blPostfix)) === $blPostfix)
					{
						$columnList['COMPANY_BY'][] = $ufKey;
					}
				}
			}
		}

		return $columnList;
	}

	public static function getFiltrableColumnGroups()
	{
		return array_merge(parent::getFiltrableColumnGroups(), ['PRODUCT_ROW']);
	}

	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		$entity->addField(array(
			'data_type' => 'string',
			'expression' => array(
				'CONCAT(\'DEAL_STAGE\', CASE WHEN %s = 0 THEN \'\' ELSE CONCAT(\'_\',%s) END)',
				'CATEGORY_ID', 'CATEGORY_ID'
			)
		), '_STAGE_ENTITY_ID');

		$entity->addField(array(
			'data_type' => 'Status',
			'reference' => array(
				'=this.STAGE_ID' => 'ref.STATUS_ID',
				'=this._STAGE_ENTITY_ID' => 'ref.ENTITY_ID'
			)
		), '_STAGE_BY_CAT');

		self::appendBooleanUserFieldsIfNull($entity);
		self::appendDateTimeUserFieldsAsShort($entity);
		self::appendMoneyUserFieldsAsSeparated($entity);
		self::appendTextUserFieldsAsTrimmed($entity);
		self::appendUTMFields($entity);
	}

	public static function getCustomSelectFields($select, $fList)
	{
		return [];
	}

	public static function getCustomColumnTypes()
	{
		return array(
			'OPPORTUNITY' => 'float',
			'OPPORTUNITY_ACCOUNT' => 'float',
			'RECEIVED_AMOUNT' => 'float',
			'LOST_AMOUNT' => 'float',
			'CATEGORY_ID' => 'string',
			'WEBFORM_ID' => 'string',
			'ProductRow:DEAL_OWNER.SUM_ACCOUNT' => 'float',
			'ProductRow:DEAL_OWNER.PRICE_ACCOUNT' => 'float',
			'COMPANY_BY.REVENUE' => 'float'
		);
	}
	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'TITLE'),
			array('name' => 'STAGE_ID'),
			array('name' => 'ASSIGNED_BY.SHORT_NAME'),
			array('name' => 'BEGINDATE_SHORT')
		);
	}
	public static function getCalcVariations()
	{
		$calcVariations = [
			'IS_WORK' => array('SUM'),
			'IS_LOSE' => array('SUM'),
			'IS_WON' => array('SUM'),
			'ProductRow:DEAL_OWNER.IBLOCK_ELEMENT.ID' => array('COUNT_DISTINCT', 'GROUP_CONCAT'),
			'ProductRow:DEAL_OWNER.CP_PRODUCT_NAME' => array('COUNT_DISTINCT', 'GROUP_CONCAT')
		];

		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$calcVariations[$fieldId.self::UTM_FIELD_POSTFIX] = ['COUNT_DISTINCT'];
		}

		return array_merge(
			parent::getCalcVariations(),
			$calcVariations
		);
	}
	public static function getCompareVariations()
	{
		return array_merge(
			parent::getCompareVariations(),
			array(
				'CATEGORY_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'STAGE_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'TYPE_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'CURRENCY_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'EVENT_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'WEBFORM_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'LEAD_BY' => array(
					'EQUAL'
				),
				'CONTACT_BY' => array(
					'EQUAL'
				),
				'COMPANY_BY' => array(
					'EQUAL'
				),
				'LEAD_BY.STATUS_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'LEAD_BY.SOURCE_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'CONTACT_BY.TYPE_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'CONTACT_BY.SOURCE_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'COMPANY_BY.INDUSTRY_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'COMPANY_BY.EMPLOYEES_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				)
			)
		);
	}
	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options, $runtime);

		self::rewriteCategoryFilter($filter);

		if(!isset($select['CRM_DEAL_COMPANY_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_DEAL_COMPANY_BY_') === 0)
				{
					$select['CRM_DEAL_COMPANY_BY_ID'] = 'COMPANY_BY.ID';
					break;
				}
			}
		}

		// HACK: Switch to order by STAGE_BY.SORT instead STAGE_BY.STATUS_ID
		// We are trying to adhere user defined sort rules.
		if(isset($order['STAGE_ID']))
		{
			$select['CRM_DEAL_STAGE_BY_SORT'] = '_STAGE_BY_CAT.SORT';
			$order['CRM_DEAL_STAGE_BY_SORT'] = $order['STAGE_ID'];
			unset($order['STAGE_ID']);
		}

		if(!isset($select['CRM_DEAL_CONTACT_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_DEAL_CONTACT_BY_') === 0)
				{
					$select['CRM_DEAL_CONTACT_BY_ID'] = 'CONTACT_BY.ID';
					break;
				}
			}
		}

		if(!isset($select['CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_') === 0)
				{
					$select['CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_ID'] = 'ProductRow:DEAL_OWNER.IBLOCK_ELEMENT.ID';
					$select['CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_IBLOCK_ID'] = 'ProductRow:DEAL_OWNER.IBLOCK_ELEMENT.IBLOCK_ID';
					break;
				}
			}

		}

		// permission
		$addClause = CCrmDeal::BuildPermSql('crm_deal');
		if($addClause === false)
		{
			// access dinied
			$filter = array($filter, '=ID' => '0');
		}
		elseif(!empty($addClause))
		{
			global $DB;
			// HACK: add escape chars for ORM
			$addClause = str_replace('crm_deal.ID', $DB->escL.'crm_deal'.$DB->escR.'.ID', $addClause);

			$filter = array($filter,
				'=IS_ALLOWED' => '1'
			);

			$runtime['IS_ALLOWED'] = array(
				'data_type' => 'integer',
				'expression' => array('CASE WHEN '.$addClause.' THEN 1 ELSE 0 END')
			);
		}

		if (!isset($filter['=IS_RECURRING']) || !isset($filter['IS_RECURRING']))
		{
			if(!isset($filter['LOGIC']) || $filter['LOGIC'] === 'AND')
			{
				$filter['=IS_RECURRING'] = 'N';
			}
			else
			{
				$filter = array(
					$filter,
					'=IS_RECURRING' => 'N'
				);

			}
		}
	}

	protected static function rewriteCategoryFilter(&$filter)
	{
		foreach ($filter as $k => &$v)
		{
			if (is_array($v))
			{
				// (CATEGORY_ID != 0 OR CATEGORY_ID IS NULL) => (CATEGORY_ID != 0)
				if (count($v) === 3 && isset($v['LOGIC']) && $v['LOGIC'] === 'OR'
					&& isset($v['!CATEGORY_ID']) && ($v['!CATEGORY_ID'] === 0 || $v['!CATEGORY_ID'] === '0')
					&& isset($v['=CATEGORY_ID']) && $v['=CATEGORY_ID'] === false)
				{
					unset($v['=CATEGORY_ID']);
				}
				else
				{
					self::rewriteCategoryFilter($v);
				}
			}
		}
	}

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		// HACK: detect if 'report.view' component is rendering excel spreadsheet
		$isHtml = !(isset($_GET['EXCEL']) && $_GET['EXCEL'] === 'Y');

		$field = $cInfo['field'];
		$fieldName = isset($cInfo['fieldName']) ? $cInfo['fieldName'] : $field->GetName();
		$prcnt = isset($cInfo['prcnt']) ? $cInfo['prcnt'] : '';
		$aggr = (!empty($cInfo['aggr']) && $cInfo['aggr'] !== 'GROUP_CONCAT');

		if(!isset($prcnt[0])
			&& ($fieldName === 'OPPORTUNITY'
				|| $fieldName === 'OPPORTUNITY_ACCOUNT'
				|| $fieldName === 'RECEIVED_AMOUNT'
				|| $fieldName === 'LOST_AMOUNT'
				|| $fieldName === 'ProductRow:DEAL_OWNER.SUM_ACCOUNT'
				|| $fieldName === 'ProductRow:DEAL_OWNER.PRICE_ACCOUNT'
				|| $fieldName === 'COMPANY_BY.REVENUE'))
		{
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'float';
			$customChartValue['value'] = doubleval($v);

			$v = self::MoneyToString(doubleval($v), $isHtml);
		}
		elseif(!$aggr && $fieldName === 'TITLE')
		{
			if($isHtml && $v <> '' && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['ID']))
			{
				$v = self::prepareDealTitleHtml(self::$CURRENT_RESULT_ROW['ID'], $v);
			}
		}
		elseif(!$aggr && $fieldName === 'CATEGORY_ID')
		{
			if($v !== '')
			{
				$v = self::getDealCategoryName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'STAGE_ID')
		{
			if($v !== '')
			{
				$v = self::getDealStageName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'TYPE_ID')
		{
			if($v !== '')
			{
				$v = self::getDealTypeName($v, $isHtml);
			}
		}
		elseif(!$aggr && ($fieldName === 'CURRENCY_ID' || $fieldName === 'LEAD_BY.CURRENCY_ID'
				|| $fieldName === 'COMPANY_BY.CURRENCY_ID'))
		{
			if($v !== '')
			{
				$v = self::getCurrencyName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'EVENT_ID')
		{
			if($v !== '')
			{
				$v = self::getEventTypeName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'ORIGINATOR_BY.ID')
		{
			$v = self::getDealOriginatorName($v, $isHtml);
		}
		elseif(!$aggr && $fieldName === 'LEAD_BY.TITLE')
		{
			if($isHtml && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['CRM_DEAL_LEAD_BY_ID']))
			{
				$v = self::prepareLeadTitleHtml(self::$CURRENT_RESULT_ROW['CRM_DEAL_LEAD_BY_ID'], $v);
			}
		}
		elseif(!$aggr && $fieldName === 'LEAD_BY.STATUS_BY.STATUS_ID')
		{
			if($v !== '')
			{
				$v = self::getStatusName($v, 'STATUS', $isHtml);
			}
		}
		elseif(!$aggr && ($fieldName === 'LEAD_BY.SOURCE_BY.STATUS_ID'
				|| $fieldName === 'CONTACT_BY.SOURCE_BY.STATUS_ID'))
		{
			if($v !== '')
			{
				$v = self::getStatusName($v, 'SOURCE', $isHtml);
			}
		}
		elseif(mb_strpos($fieldName, 'COMPANY_BY.') === 0)
		{
			if(!$aggr && ($v === '' || trim($v) === '.'))
			{
				if(mb_strpos($fieldName, 'COMPANY_BY.COMPANY_TYPE_BY') !== 0
					&& mb_strpos($fieldName, 'COMPANY_BY.INDUSTRY_BY') !== 0
					&& mb_strpos($fieldName, 'COMPANY_BY.EMPLOYEES_BY') !== 0)
				{
					$v = GetMessage('CRM_DEAL_COMPANY_NOT_ASSIGNED');
				}
			}
			elseif(!$aggr && $fieldName === 'COMPANY_BY.TITLE')
			{
				if($isHtml && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['CRM_DEAL_COMPANY_BY_ID']))
				{
					$v = self::prepareCompanyTitleHtml(self::$CURRENT_RESULT_ROW['CRM_DEAL_COMPANY_BY_ID'], $v);
				}
			}
			elseif(!$aggr && $fieldName === 'COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'COMPANY_TYPE', $isHtml);
				}
			}
			elseif(!$aggr && $fieldName === 'COMPANY_BY.INDUSTRY_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'INDUSTRY', $isHtml);
				}
			}
			elseif(!$aggr && $fieldName === 'COMPANY_BY.EMPLOYEES_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'EMPLOYEES', $isHtml);
				}
			}
			elseif($fieldName !== 'COMPANY_BY.COMMENTS')
			{
				parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
			}
		}
		elseif(mb_strpos($fieldName, 'CONTACT_BY.') === 0)
		{
			if(!$aggr && ($v === '' || trim($v) === '.'))
			{
				if(mb_strpos($fieldName, 'CONTACT_BY.TYPE_BY') !== 0)
				{
					$v = GetMessage('CRM_DEAL_CONTACT_NOT_ASSIGNED');
				}
			}
			elseif(!$aggr && $fieldName === 'CONTACT_BY.TYPE_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'CONTACT_TYPE', $isHtml);
				}
			}
			elseif(!$aggr && ($fieldName === 'CONTACT_BY.NAME'
					|| $fieldName === 'CONTACT_BY.LAST_NAME'
					|| $fieldName === 'CONTACT_BY.SECOND_NAME'
					|| $fieldName === 'CONTACT_BY.ADDRESS'))
			{
				if($isHtml && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['CRM_DEAL_CONTACT_BY_ID']))
				{
					$v = self::prepareContactTitleHtml(self::$CURRENT_RESULT_ROW['CRM_DEAL_CONTACT_BY_ID'], $v);
				}
			}
			elseif($fieldName !== 'CONTACT_BY.COMMENTS')
			{
				parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'ASSIGNED_BY.') === 0)
		{
			// unset HREF for empty value
			if (empty($v) || trim($v) === '.' || $v === '&nbsp;')
				unset($row['__HREF_'.$k]);
			if($v == '' || trim($v) === '.')
			{
				$v = GetMessage('CRM_DEAL_RESPONSIBLE_NOT_ASSIGNED');
			}
			elseif($isHtml)
			{
				$v = htmlspecialcharsbx($v);
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'ProductRow:DEAL_OWNER.IBLOCK_ELEMENT.') === 0)
		{
			static $defaultCatalogID;
			if(!isset($defaultCatalogID))
			{
				$defaultCatalogID = CCrmCatalog::GetDefaultID();
			}

			if($isHtml)
			{
				if($defaultCatalogID > 0 && self::$CURRENT_RESULT_ROW)
				{
					$iblockID = isset(self::$CURRENT_RESULT_ROW['CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_IBLOCK_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_IBLOCK_ID']) : 0;;
					$iblockElementID = isset(self::$CURRENT_RESULT_ROW['CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_DEAL_CRM_PRODUCT_ROW_DEAL_OWNER_IBLOCK_ELEMENT_ID']) : 0;
				}
				else
				{
					$iblockID = 0;
					$iblockElementID = 0;
				}

				if($iblockElementID > 0 && $iblockID === $defaultCatalogID)
				{
					$v = self::prepareProductNameHtml($iblockElementID, $v);
				}
				else
				{
					$v = htmlspecialcharsbx($v);
				}
			}
		}
		elseif(!$aggr && $fieldName === 'WEBFORM_ID')
		{
			if($v !== '')
			{
				$v = self::getWebFormName($v, $isHtml);
			}
		}
		elseif($fieldName !== 'COMMENTS' && $fieldName !== 'LEAD_BY.COMMENTS') // Leave 'COMMENTS' as is for HTML display.
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
		}
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		if(is_null($date_from) && is_null($date_to))
		{
			return array(); // Empty filter for empty time interval.
		}

		//$now = ConvertTimeStamp(time(), 'FULL');
		$filter = array('LOGIC' => 'AND');
		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=BEGINDATE_SHORT' => $date_to,
				'=BEGINDATE_SHORT' => null
			);
			//$filter['<=BEGINDATE_SHORT'] = $date_to;
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=CLOSEDATE_SHORT' => $date_from,
				'=CLOSEDATE_SHORT' => null
			);
			//$filter['>=CLOSEDATE_SHORT'] = $date_from;
		}

		return $filter;
	}

	public static function clearMenuCache()
	{
		CrmClearMenuCache();
	}

	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		parent::formatResultsTotal($total, $columnInfo, $customChartTotal);

		if(isset($total['TOTAL_PROBABILITY']))
		{
			// Suppress PROBABILITY (%) aggregation
			unset($total['TOTAL_PROBABILITY']);
		}
		elseif(isset($total['TOTAL_CATEGORY_ID']))
		{
			// Suppress CATEGORY_ID aggregation
			unset($total['TOTAL_CATEGORY_ID']);
		}
		if(isset($total['TOTAL_WEBFORM_ID']))
		{
			// Suppress WEBFORM_ID aggregation
			unset($total['TOTAL_WEBFORM_ID']);
		}
	}

	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'11.0.6' => array(
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_WON_DEALS'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_WON_DEALS_DESCR'),
					'mark_default' => 1,
					'settings' => unserialize('a:7:{s:6:"entity";s:7:"CrmDeal";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:0;a:2:{s:4:"name";s:5:"TITLE";s:5:"alias";s:0:"";}i:20;a:2:{s:4:"name";s:7:"TYPE_ID";s:5:"alias";s:0:"";}i:2;a:2:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";s:5:"alias";s:0:"";}i:7;a:2:{s:4:"name";s:16:"COMPANY_BY.TITLE";s:5:"alias";s:0:"";}i:23;a:2:{s:4:"name";s:36:"COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID";s:5:"alias";s:0:"";}i:6;a:2:{s:4:"name";s:20:"CONTACT_BY.LAST_NAME";s:5:"alias";s:0:"";}i:27;a:2:{s:4:"name";s:15:"RECEIVED_AMOUNT";s:5:"alias";s:0:"";}i:4;a:2:{s:4:"name";s:15:"CLOSEDATE_SHORT";s:5:"alias";s:0:"";}}s:6:"filter";a:1:{i:0;a:7:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"CONTACT_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"STAGE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:3:"WON";s:10:"changeable";s:1:"0";}i:5;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:4;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_PRODUCTS_PROFIT'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_PRODUCTS_PROFIT_DESCR'),
					'mark_default' => 2,
					'settings' => unserialize('a:7:{s:6:"entity";s:7:"CrmDeal";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:4:{i:4;a:2:{s:4:"name";s:37:"ProductRow:DEAL_OWNER.CP_PRODUCT_NAME";s:5:"alias";s:0:"";}i:5;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:6;a:3:{s:4:"name";s:30:"ProductRow:DEAL_OWNER.QUANTITY";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:7;a:3:{s:4:"name";s:33:"ProductRow:DEAL_OWNER.SUM_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:9:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:37:"ProductRow:DEAL_OWNER.CP_PRODUCT_NAME";s:7:"compare";s:8:"CONTAINS";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"STAGE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:3:"WON";s:10:"changeable";s:1:"0";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:36:"COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";s:5:"field";s:4:"name";s:28:"CONTACT_BY.TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:6;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"CONTACT_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:7;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:7;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_VOLUME_BY_CONTACTS'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_VOLUME_BY_CONTACTS_DESCR'),
					'mark_default' => 3,
					'settings' => unserialize('a:10:{s:6:"entity";s:15:"Bitrix\Crm\Deal";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:4;a:2:{s:4:"name";s:20:"CONTACT_BY.LAST_NAME";s:5:"alias";s:0:"";}i:5;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:6;a:3:{s:4:"name";s:7:"IS_WORK";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:4:{s:4:"name";s:7:"IS_LOSE";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"5";}i:7;a:4:{s:4:"name";s:6:"IS_WON";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"5";}i:9;a:3:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"AVG";}i:10;a:3:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:12;a:3:{s:4:"name";s:15:"RECEIVED_AMOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:6:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:28:"CONTACT_BY.TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"CONTACT_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"CONTACT_BY.ID";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:12;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"pie";s:8:"x_column";i:4;s:9:"y_columns";a:1:{i:0;i:12;}}}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_VOLUME_BY_COMPANIES'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_VOLUME_BY_COMPANIES_DESCR'),
					'mark_default' => 4,
					'settings' => unserialize('a:10:{s:6:"entity";s:15:"Bitrix\Crm\Deal";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:4;a:2:{s:4:"name";s:16:"COMPANY_BY.TITLE";s:5:"alias";s:0:"";}i:5;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:6;a:3:{s:4:"name";s:7:"IS_WORK";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:4:{s:4:"name";s:7:"IS_LOSE";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"5";}i:7;a:4:{s:4:"name";s:6:"IS_WON";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"5";}i:9;a:3:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"AVG";}i:10;a:3:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:12;a:3:{s:4:"name";s:15:"RECEIVED_AMOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:6:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:36:"COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"COMPANY_BY.ID";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:12;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"pie";s:8:"x_column";i:4;s:9:"y_columns";a:1:{i:0;i:12;}}}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_VOLUME_BY_MANAGERS'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_VOLUME_BY_MANAGERS_DESCR'),
					'mark_default' => 5,
					'settings' => unserialize('a:7:{s:6:"entity";s:7:"CrmDeal";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:2;a:2:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";s:5:"alias";s:0:"";}i:4;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:5;a:3:{s:4:"name";s:7:"IS_WORK";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:7;a:4:{s:4:"name";s:7:"IS_LOSE";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"4";}i:6;a:4:{s:4:"name";s:6:"IS_WON";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:1:"4";}i:11;a:3:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"AVG";}i:10;a:3:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:9;a:3:{s:4:"name";s:15:"RECEIVED_AMOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:7:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"CONTACT_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:28:"CONTACT_BY.TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";s:5:"field";s:4:"name";s:36:"COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:9;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_EXPECTED_SALES'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_EXPECTED_SALES_DESCR'),
					'mark_default' => 6,
					'settings' => unserialize('a:7:{s:6:"entity";s:7:"CrmDeal";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:9:{i:0;a:2:{s:4:"name";s:5:"TITLE";s:5:"alias";s:0:"";}i:2;a:2:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";s:5:"alias";s:0:"";}i:1;a:1:{s:4:"name";s:8:"STAGE_ID";}i:15;a:1:{s:4:"name";s:11:"PROBABILITY";}i:7;a:2:{s:4:"name";s:16:"COMPANY_BY.TITLE";s:5:"alias";s:0:"";}i:6;a:2:{s:4:"name";s:20:"CONTACT_BY.LAST_NAME";s:5:"alias";s:0:"";}i:3;a:1:{s:4:"name";s:15:"BEGINDATE_SHORT";}i:4;a:1:{s:4:"name";s:15:"CLOSEDATE_SHORT";}i:14;a:2:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";}}s:6:"filter";a:1:{i:0;a:10:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"STAGE_ID";s:7:"compare";s:9:"NOT_EQUAL";s:5:"value";s:3:"WON";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"STAGE_ID";s:7:"compare";s:9:"NOT_EQUAL";s:5:"value";s:4:"LOSE";s:10:"changeable";s:1:"0";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"PROBABILITY";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:2:"50";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:6;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"CONTACT_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:7;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:8;a:5:{s:4:"type";s:5:"field";s:4:"name";s:15:"CLOSEDATE_SHORT";s:7:"compare";s:13:"LESS_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:4;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_DELAYED_DEALS'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_DELAYED_DEALS_DESCR'),
					'mark_default' => 7,
					'settings' => unserialize('a:7:{s:6:"entity";s:7:"CrmDeal";s:6:"period";a:2:{s:4:"type";s:3:"all";s:5:"value";N;}s:6:"select";a:10:{i:0;a:2:{s:4:"name";s:5:"TITLE";s:5:"alias";s:0:"";}i:2;a:2:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";s:5:"alias";s:0:"";}i:15;a:2:{s:4:"name";s:7:"TYPE_ID";s:5:"alias";s:0:"";}i:1;a:1:{s:4:"name";s:8:"STAGE_ID";}i:6;a:1:{s:4:"name";s:11:"PROBABILITY";}i:7;a:2:{s:4:"name";s:20:"CONTACT_BY.LAST_NAME";s:5:"alias";s:0:"";}i:8;a:2:{s:4:"name";s:16:"COMPANY_BY.TITLE";s:5:"alias";s:0:"";}i:3;a:1:{s:4:"name";s:15:"BEGINDATE_SHORT";}i:4;a:1:{s:4:"name";s:15:"CLOSEDATE_SHORT";}i:14;a:2:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";}}s:6:"filter";a:1:{i:0;a:11:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"STAGE_ID";s:7:"compare";s:9:"NOT_EQUAL";s:5:"value";s:3:"WON";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:8:"STAGE_ID";s:7:"compare";s:9:"NOT_EQUAL";s:5:"value";s:4:"LOSE";s:10:"changeable";s:1:"0";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:6:"CLOSED";s:7:"compare";s:5:"EQUAL";s:5:"value";s:5:"false";s:10:"changeable";s:1:"0";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"PROBABILITY";s:7:"compare";s:16:"GREATER_OR_EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:6;a:5:{s:4:"type";s:5:"field";s:4:"name";s:36:"COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:7;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:8;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:9;a:5:{s:4:"type";s:5:"field";s:4:"name";s:15:"CLOSEDATE_SHORT";s:7:"compare";s:13:"LESS_OR_EQUAL";s:5:"value";s:5:"today";s:10:"changeable";s:1:"0";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:4;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;}', ['allowed_classes' => false])
				),

				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_DISTRIBUTION_BY_STAGE'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_DISTRIBUTION_BY_STAGE_DESCR'),
					'mark_default' => 8,
					'settings' => unserialize('a:10:{s:6:"entity";s:15:"Bitrix\Crm\Deal";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:5:{i:8;a:1:{s:4:"name";s:8:"STAGE_ID";}i:7;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:12;a:4:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";s:5:"prcnt";s:11:"self_column";}i:9;a:3:{s:4:"name";s:19:"OPPORTUNITY_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:11;a:3:{s:4:"name";s:15:"RECEIVED_AMOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:7:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:7:"TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:36:"COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:28:"CONTACT_BY.TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:10:"CONTACT_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:8;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:8;s:9:"y_columns";a:1:{i:0;i:7;}}}', ['allowed_classes' => false])
				)
			)
		);

//		global $DB;
//		$dbType = strtoupper($DB->type);
//		if($dbType === 'MSSQL')
//		{
//			unset($reports['11.0.6'][1]); //PRODUCTS_PROFIT is not supported in MSSQL
//		}
		unset($reports['11.0.6'][1]); //PRODUCTS_PROFIT defined in other helper

		foreach ($reports as &$vreports)
		{
			foreach ($vreports as &$report)
			{
				if ($report['mark_default'] === 1)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEAL');
					$report['settings']['select'][20]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEAL_TYPE');
					$report['settings']['select'][2]['alias'] = GetMessage('CRM_REPORT_ALIAS_RESPONSIBLE');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_COMPANY');
					$report['settings']['select'][23]['alias'] = GetMessage('CRM_REPORT_ALIAS_COMPANY_TYPE');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_ALIAS_CONTACT');
					$report['settings']['select'][27]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_PROFIT');
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_ALIAS_CLOSING_DATE');
				}
				elseif ($report['mark_default'] === 2)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_ALIAS_PRODUCT');
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_QUANTITY');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_ALIAS_SOLD_PRODUCTS_QUANTITY');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_SALES_PROFIT');
				}
				elseif ($report['mark_default'] === 3)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_ALIAS_LAST_NAME');
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_QUANTITY');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_INPROCESS_QUANTITY');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_LOSE_QUANTITY');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_WON_QUANTITY');
					$report['settings']['select'][9]['alias'] = GetMessage('CRM_REPORT_ALIAS_AVERAGE_DEAL');
					$report['settings']['select'][10]['alias'] = GetMessage('CRM_REPORT_ALIAS_OPPORTUNITY_AMOUNT');
					$report['settings']['select'][12]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_PROFIT');
				}
				elseif ($report['mark_default'] === 4)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_ALIAS_COMPANY');
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_QUANTITY');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_INPROCESS_QUANTITY');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_LOSE_QUANTITY');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_WON_QUANTITY');
					$report['settings']['select'][9]['alias'] = GetMessage('CRM_REPORT_ALIAS_AVERAGE_DEAL');
					$report['settings']['select'][10]['alias'] = GetMessage('CRM_REPORT_ALIAS_OPPORTUNITY_AMOUNT');
					$report['settings']['select'][12]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_PROFIT');
				}
				elseif ($report['mark_default'] === 5)
				{
					$report['settings']['select'][2]['alias'] = GetMessage('CRM_REPORT_ALIAS_RESPONSIBLE');
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_QUANTITY');
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_INPROCESS_QUANTITY');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_LOSE_QUANTITY');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_WON_QUANTITY');
					$report['settings']['select'][11]['alias'] = GetMessage('CRM_REPORT_ALIAS_AVERAGE_DEAL');
					$report['settings']['select'][10]['alias'] = GetMessage('CRM_REPORT_ALIAS_OPPORTUNITY_AMOUNT');
					$report['settings']['select'][9]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_PROFIT');
				}
				elseif ($report['mark_default'] === 6)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEAL');
					$report['settings']['select'][2]['alias'] = GetMessage('CRM_REPORT_ALIAS_RESPONSIBLE');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_COMPANY');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_ALIAS_CONTACT');
					$report['settings']['select'][14]['alias'] = GetMessage('CRM_REPORT_ALIAS_OPPORTUNITY_AMOUNT');
				}
				elseif ($report['mark_default'] === 7)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEAL');
					$report['settings']['select'][2]['alias'] = GetMessage('CRM_REPORT_ALIAS_RESPONSIBLE');
					$report['settings']['select'][15]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEAL_TYPE');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_CONTACT');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_ALIAS_COMPANY');
					$report['settings']['select'][14]['alias'] = GetMessage('CRM_REPORT_ALIAS_OPPORTUNITY_AMOUNT');
				}
				elseif ($report['mark_default'] === 8)
				{
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_QUANTITY');
					$report['settings']['select'][12]['alias'] = GetMessage('CRM_REPORT_ALIAS_PROPORTION');
					$report['settings']['select'][9]['alias'] = GetMessage('CRM_REPORT_ALIAS_OPPORTUNITY_AMOUNT');
					$report['settings']['select'][11]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_PROFIT');
				}
			}
			unset($report);
		}

		return $reports;
	}

	public static function getFirstVersion()
	{
		return '11.0.6';
	}
}

class CCrmInvoiceReportHelper extends CCrmReportHelperBase
{
	protected static function prepareUFInfo()
	{
		if (is_array(self::$arUFId))
			return;

		self::$arUFId = array('CRM_INVOICE');
		parent::prepareUFInfo();
	}

	public static function GetReportCurrencyID()
	{
		return CCrmReportManager::GetReportCurrencyID();
	}

	public static function SetReportCurrencyID($currencyID)
	{
		CCrmReportManager::SetReportCurrencyID($currencyID);
	}

	public static function getEntityName()
	{
		return 'Bitrix\Crm\Invoice';
	}
	public static function getOwnerId()
	{
		return 'crm_invoice';
	}
	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		$columnList = array(
			'ID',
			'ACCOUNT_NUMBER',
			'ORDER_TOPIC',
			'STATUS_ID',
			'STATUS_SUB' => array(
				'IS_WORK',
				'IS_PAYED',
				'IS_CANCELED'
			),
			'DATE_BILL_SHORT',
			'DATE_FINISHED_SHORT',
			'DATE_PAY_BEFORE_SHORT',
			'PAY_VOUCHER_DATE_SHORT',
			'PAY_VOUCHER_NUM',
			'DATE_MARKED_SHORT',
			'REASON_MARKED',
			'PRICE',
			'PRICE_WORK',
			'PRICE_PAYED',
			'PRICE_CANCELED',
			'CURRENCY',
			'PERSON_TYPE_ID',
			'PAY_SYSTEM_ID',
			'ASSIGNED_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'INVOICE_UTS.DEAL_BY' => array(
				'ID',
				'TITLE',
				'WEBFORM_ID',
				'IS_RETURN_CUSTOMER_BL'
			),
			'INVOICE_UTS.CONTACT_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'SECOND_NAME',
				'BIRTHDATE'
			),
			'INVOICE_UTS.COMPANY_BY' => array(
				'ID',
				'TITLE'
			),
			'InvoiceSpec:INVOICE' => array(
				'ID',
				'PRODUCT_ID',
				'NAME',
				'IBLOCK_ELEMENT.NAME',
				'QUANTITY',
				'PRICE',
				'VAT_RATE_PRC',
				'SUMMARY_PRICE'
			)
		);

		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$columnList['INVOICE_UTS.DEAL_BY'][] = $fieldId.self::UTM_FIELD_POSTFIX;
		}

		// Append user fields
		$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
		self::prepareUFInfo();
		if (is_array(self::$ufInfo) && count(self::$ufInfo) > 0)
		{
			if (isset(self::$ufInfo['CRM_INVOICE']) && is_array(self::$ufInfo['CRM_INVOICE'])
				&& count(self::$ufInfo['CRM_INVOICE']) > 0)
			{
				foreach (self::$ufInfo['CRM_INVOICE'] as $ufKey => $uf)
				{
					if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
						|| $uf['MULTIPLE'] === 'Y'
						|| mb_substr($ufKey, -mb_strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
						|| mb_substr($ufKey, -mb_strlen($blPostfix)) === $blPostfix)
					{
						$columnList[] = $ufKey;
					}
				}
			}
		}

		return $columnList;
	}

	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		global $DB;

		$options = array();

		Crm\InvoiceTable::processQueryOptions($options);

		$entity->addField(array(
			'data_type' => 'float',
			'expression' => array(
				'CASE WHEN %s IN '.$options['WORK_STATUS_IDS'].' THEN %s ELSE 0 END',
				'STATUS_ID', 'PRICE'
			),
			'values' => array(0, 1)
		), 'PRICE_WORK');

		$entity->addField(array(
			'data_type' => 'float',
			'expression' => array(
				'CASE WHEN %s IN '.$options['CANCEL_STATUS_IDS'].' THEN %s ELSE 0 END',
				'STATUS_ID', 'PRICE'
			),
			'values' => array(0, 1)
		), 'PRICE_CANCELED');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s IN '.$options['WORK_STATUS_IDS'].' THEN 1 ELSE 0 END',
				'STATUS_ID'
			),
			'values' => array(0, 1)
		), 'IS_WORK');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s IN '.$options['CANCEL_STATUS_IDS'].' THEN 1 ELSE 0 END',
				'STATUS_ID'
			),
			'values' => array(0, 1)
		), 'IS_CANCELED');

		$datetimeNull = 'CAST(NULL AS DATETIME)';

		$entity->addField(array(
			'data_type' => 'datetime',
			'expression' => array(
				'CASE WHEN %s = \'P\' THEN '.$DB->datetimeToDateFunction($DB->IsNull('%s', '%s')).
				' WHEN %s IN '.$options['CANCEL_STATUS_IDS'].' THEN '.$DB->datetimeToDateFunction($DB->IsNull('%s', '%s')).
				' ELSE '.$datetimeNull.' END',
				'STATUS_ID', 'PAY_VOUCHER_DATE', 'DATE_INSERT',
				'STATUS_ID', 'DATE_MARKED', 'DATE_INSERT'
			)
		), 'DATE_FINISHED_SHORT');

		self::appendBooleanUserFieldsIfNull($entity);
		self::appendDateTimeUserFieldsAsShort($entity);
		self::appendMoneyUserFieldsAsSeparated($entity);
		self::appendTextUserFieldsAsTrimmed($entity);
		self::appendUTMFields($entity);
	}

	public static function getCustomSelectFields($select, $fList)
	{
		return [];
	}

	public static function getCustomColumnTypes()
	{
		return array(
			'STATUS_ID' => 'string',
			'PAY_SYSTEM_ID' => 'string',
			'PERSON_TYPE_ID' => 'string',
			'CURRENCY' => 'string',
			'INVOICE_UTS.DEAL_BY.WEBFORM_ID' => 'string'
		);
	}
	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'ACCOUNT_NUMBER'),
			array('name' => 'ORDER_TOPIC'),
			array('name' => 'DATE_INS'),
			array('name' => 'STATUS_ID'),
			array('name' => 'ASSIGNED_BY.SHORT_NAME')
		);
	}
	public static function getCalcVariations()
	{
		$calcVariations = [
			'IS_WORK' => array('SUM'),
			'IS_CANCELED' => array('SUM'),
			'IS_PAYED' => array('SUM'),
			'InvoiceSpec:INVOICE.ID' => array('COUNT_DISTINCT', 'GROUP_CONCAT'),
			'InvoiceSpec:INVOICE.PRODUCT_ID' => array('COUNT_DISTINCT', 'GROUP_CONCAT'),
			'InvoiceSpec:INVOICE.NAME' => array('GROUP_CONCAT'),
			'InvoiceSpec:INVOICE.IBLOCK_ELEMENT.NAME' => array('GROUP_CONCAT')
		];

		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$calcVariations['INVOICE_UTS.DEAL_BY.'.$fieldId.self::UTM_FIELD_POSTFIX] = ['COUNT_DISTINCT'];
		}

		return array_merge(
			parent::getCalcVariations(),
			$calcVariations
		);
	}
	public static function getCompareVariations()
	{
		return array_merge(
			parent::getCompareVariations(),
			[
				'STATUS_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'PAY_SYSTEM_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'PERSON_TYPE_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'CURRENCY' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'INVOICE_UTS.DEAL_BY' => [
					'EQUAL'
				],
				'INVOICE_UTS.DEAL_BY.WEBFORM_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'INVOICE_UTS.CONTACT_BY' => [
					'EQUAL'
				],
				'INVOICE_UTS.COMPANY_BY' => [
					'EQUAL'
				]
			]
		);
	}
	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options, $runtime);

		if(!isset($select['CRM_INVOICE_INVOICE_UTS_COMPANY_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_INVOICE_INVOICE_UTS_COMPANY_BY_') === 0)
				{
					$select['CRM_INVOICE_INVOICE_UTS_COMPANY_BY_ID'] = 'INVOICE_UTS.COMPANY_BY.ID';
					break;
				}
			}
		}
		if(!isset($select['CRM_INVOICE_INVOICE_UTS_CONTACT_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_INVOICE_INVOICE_UTS_CONTACT_BY_') === 0)
				{
					$select['CRM_INVOICE_INVOICE_UTS_CONTACT_BY_ID'] = 'INVOICE_UTS.CONTACT_BY.ID';
					break;
				}
			}
		}

		// HACK: Switch to order by SATTUS_BY.SORT instead STATUS_BY.STATUS_ID
		// We are trying to adhere user defined sort rules.
		if(isset($order['STATUS_ID']))
		{
			$select['CRM_INVOICE_STATUS_BY_SORT'] = 'STATUS_BY.SORT';
			$order['CRM_INVOICE_STATUS_BY_SORT'] = $order['STATUS_ID'];
			unset($order['STATUS_ID']);
		}

		if(!isset($select['CRM_INVOICE_INVOICE_UTS_CONTACT_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_INVOICE_INVOICE_UTS_CONTACT_BY_') === 0)
				{
					$select['CRM_INVOICE_INVOICE_UTS_CONTACT_BY_ID'] = 'INVOICE_UTS.CONTACT_BY.ID';
					break;
				}
			}
		}

		if(!isset($select['CRM_INVOICE_INVOICE_UTS_COMPANY_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_INVOICE_INVOICE_UTS_COMPANY_BY_') === 0)
				{
					$select['CRM_INVOICE_INVOICE_UTS_COMPANY_BY_ID'] = 'INVOICE_UTS.COMPANY_BY.ID';
					break;
				}
			}
		}

		// permission
		$addClause = CCrmInvoice::BuildPermSql('crm_invoice');
		if(!empty($addClause))
		{
			global $DB;
			// HACK: add escape chars for ORM
			$addClause = str_replace('crm_invoice.ID', $DB->escL.'crm_invoice'.$DB->escR.'.ID', $addClause);

			$filter = array($filter,
				'=IS_ALLOWED' => '1'
			);

			$runtime['IS_ALLOWED'] = array(
				'data_type' => 'integer',
				'expression' => array('CASE WHEN '.$addClause.' THEN 1 ELSE 0 END')
			);
		}

		if (!isset($filter['=IS_RECURRING']) || !isset($filter['IS_RECURRING']))
		{
			if(!isset($filter['LOGIC']) || $filter['LOGIC'] === 'AND')
			{
				$filter['=IS_RECURRING'] = 'N';
			}
			else
			{
				$filter = array(
					$filter,
					'=IS_RECURRING' => 'N'
				);

			}
		}
	}

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		// HACK: detect if 'report.view' component is rendering excel spreadsheet
		$isHtml = !(isset($_GET['EXCEL']) && $_GET['EXCEL'] === 'Y');

		$field = $cInfo['field'];
		$fieldName = isset($cInfo['fieldName']) ? $cInfo['fieldName'] : $field->GetName();
		$prcnt = isset($cInfo['prcnt']) ? $cInfo['prcnt'] : '';
		$aggr = (!empty($cInfo['aggr']) && $cInfo['aggr'] !== 'GROUP_CONCAT');

		if (!isset($prcnt[0])
			&& ($k === 'PRICE'
				|| preg_match('/.PRICE$/', $k)
				|| $k === 'PRICE_WORK' || preg_match('/_PRICE_WORK$/', $k)
				|| $k === 'PRICE_PAYED' || preg_match('/_PRICE_PAYED$/', $k)
				|| $k === 'PRICE_CANCELED' || preg_match('/_PRICE_CANCELED/', $k)))
		{
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'float';
			$customChartValue['value'] = doubleval($v);

			$v = self::MoneyToString(doubleval($v), $isHtml);
		}
		elseif (!isset($prcnt[0]) && preg_match('/_VAT_RATE_PRC$/', $k))
		{
			$v = number_format(doubleval($v), 2, '.', '');
			if ($isHtml)
				$v = str_replace(' ', '&nbsp;', $v);
		}
		elseif (!isset($prcnt[0]) && preg_match('/_QUANTITY$/', $k))
		{
			$v = round(doubleval($v), 4);
		}
		elseif(!$aggr && $fieldName === 'ORDER_TOPIC')
		{
			if($isHtml && $v <> '' && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['ID']))
			{
				$v = self::prepareInvoiceTitleHtml(self::$CURRENT_RESULT_ROW['ID'], $v);
			}
		}
		elseif(!$aggr && $fieldName === 'PAY_SYSTEM_ID')
		{
			if($v !== '')
			{
				$personTypeId = intval(self::$CURRENT_RESULT_ROW['PERSON_TYPE_ID']);
				$v = self::getInvoicePaySystemName($v, $personTypeId, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'PERSON_TYPE_ID')
		{
			if($v !== '')
			{
				$v = self::getInvoicePersonTypeName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'STATUS_ID')
		{
			if($v !== '')
			{
				$v = self::getInvoiceStatusName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'CURRENCY')
		{
			if($v !== '')
			{
				$v = self::getCurrencyName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'INVOICE_UTS.DEAL_BY.WEBFORM_ID')
		{
			if($v !== '')
			{
				$v = self::getWebFormName($v, $isHtml);
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'ASSIGNED_BY.') === 0)
		{
			// unset HREF for empty value
			if (empty($v) || trim($v) === '.' || $v === '&nbsp;')
				unset($row['__HREF_'.$k]);
			if(($v == '' || trim($v) === '.') && mb_strpos($fieldName, '.WORK_POSITION') !== mb_strlen($fieldName) - 14)
			{
				$v = GetMessage('CRM_INVOICE_RESPONSIBLE_NOT_ASSIGNED');
			}
			if($isHtml)
			{
				$v = htmlspecialcharsbx($v);
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'ProductRow:LEAD_OWNER.IBLOCK_ELEMENT.') === 0)
		{
			static $defaultCatalogID;
			if(!isset($defaultCatalogID))
			{
				$defaultCatalogID = CCrmCatalog::GetDefaultID();
			}

			if($isHtml)
			{
				if($defaultCatalogID > 0 && self::$CURRENT_RESULT_ROW)
				{
					$iblockID = isset(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_IBLOCK_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_IBLOCK_ID']) : 0;;
					$iblockElementID = isset(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_ID']) : 0;
				}
				else
				{
					$iblockID = 0;
					$iblockElementID = 0;
				}

				if($iblockElementID > 0 && $iblockID === $defaultCatalogID)
				{
					$v = self::prepareProductNameHtml($iblockElementID, $v);
				}
				else
				{
					$v = htmlspecialcharsbx($v);
				}
			}
		}
		elseif($fieldName !== 'COMMENTS') // Leave 'COMMENTS' as is for HTML display.
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
		}
	}

	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		parent::formatResultsTotal($total, $columnInfo);

		// Suppress total values
		if (isset($total['TOTAL_PAY_SYSTEM_ID']))
		{
			unset($total['TOTAL_PAY_SYSTEM_ID']);
		}
		if (isset($total['TOTAL_PERSON_TYPE_ID']))
		{
			unset($total['TOTAL_PERSON_TYPE_ID']);
		}
		if (isset($total['TOTAL_CRM_INVOICE_CRM_INVOICE_SPEC_INVOICE_ID']))
		{
			unset($total['TOTAL_CRM_INVOICE_CRM_INVOICE_SPEC_INVOICE_ID']);
		}
		if (isset($total['TOTAL_CRM_INVOICE_CRM_INVOICE_SPEC_INVOICE_PRODUCT_ID']))
		{
			unset($total['TOTAL_CRM_INVOICE_CRM_INVOICE_SPEC_INVOICE_PRODUCT_ID']);
		}
		if (isset($total['TOTAL_CRM_INVOICE_CRM_INVOICE_SPEC_INVOICE_VAT_RATE_PRC']))
		{
			unset($total['TOTAL_CRM_INVOICE_CRM_INVOICE_SPEC_INVOICE_VAT_RATE_PRC']);
		}
		if(isset($total['TOTAL_CRM_INVOICE_INVOICE_UTS_DEAL_BY_WEBFORM_ID']))
		{
			// Suppress WEBFORM_ID aggregation
			unset($total['TOTAL_CRM_INVOICE_INVOICE_UTS_DEAL_BY_WEBFORM_ID']);
		}
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		if(is_null($date_from) && is_null($date_to))
		{
			return array();
		}

		$filter = array('LOGIC' => 'AND');
		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=DATE_BEGIN_SHORT' => $date_to,
				'=DATE_BEGIN_SHORT' => null
			);
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=DATE_FINISHED_SHORT' => $date_from,
				'=DATE_FINISHED_SHORT' => null
			);
		}

		return $filter;
	}

	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'14.1.0' => array(
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_MANAGER'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_MANAGER_DESCR'),
					'mark_default' => 1,
					'settings' => unserialize('a:10:{s:6:"entity";s:18:"Bitrix\Crm\Invoice";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:5:{i:4;a:2:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";s:5:"alias";s:0:"";}i:8;a:3:{s:4:"name";s:8:"IS_PAYED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:6;a:3:{s:4:"name";s:11:"PRICE_PAYED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:9;a:3:{s:4:"name";s:11:"IS_CANCELED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:7;a:3:{s:4:"name";s:14:"PRICE_CANCELED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:2:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:6;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:4;s:9:"y_columns";a:1:{i:0;i:6;}}}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_COMPANY'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_COMPANY_DESCR'),
					'mark_default' => 2,
					'settings' => unserialize('a:10:{s:6:"entity";s:18:"Bitrix\Crm\Invoice";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:3:{i:12;a:2:{s:4:"name";s:28:"INVOICE_UTS.COMPANY_BY.TITLE";s:5:"alias";s:0:"";}i:8;a:3:{s:4:"name";s:8:"IS_PAYED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:6;a:3:{s:4:"name";s:11:"PRICE_PAYED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:25:"INVOICE_UTS.COMPANY_BY.ID";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"PRICE_PAYED";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:6;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:12;s:9:"y_columns";a:1:{i:0;i:6;}}}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_CONTACT'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_CONTACT_DESCR'),
					'mark_default' => 3,
					'settings' => unserialize('a:10:{s:6:"entity";s:18:"Bitrix\Crm\Invoice";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:3:{i:15;a:1:{s:4:"name";s:33:"INVOICE_UTS.CONTACT_BY.SHORT_NAME";}i:8;a:3:{s:4:"name";s:8:"IS_PAYED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:6;a:3:{s:4:"name";s:11:"PRICE_PAYED";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:4:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:25:"INVOICE_UTS.CONTACT_BY.ID";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"PRICE_PAYED";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:6;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:15;s:9:"y_columns";a:1:{i:0;i:6;}}}', ['allowed_classes' => false])
				)
			)
		);

		foreach ($reports as &$reportByVersion)
		{
			foreach ($reportByVersion as &$report)
			{
				if ($report['mark_default'] === 1)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_MANAGER_ALIAS_4');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_MANAGER_ALIAS_6');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_MANAGER_ALIAS_7');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_MANAGER_ALIAS_8');
					$report['settings']['select'][9]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_MANAGER_ALIAS_9');
				}
				else if ($report['mark_default'] === 2)
				{
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_COMPANY_ALIAS_6');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_COMPANY_ALIAS_8');
					$report['settings']['select'][12]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_COMPANY_ALIAS_12');
				}
				else if ($report['mark_default'] === 3)
				{
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_CONTACT_ALIAS_6');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_DEFAULT_INVOICES_BY_CONTACT_ALIAS_8');
				}
			}
			unset($report);
		}
		unset($reportByVersion);

		return $reports;
	}

	public static function getFirstVersion()
	{
		return '14.0.0';
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		$href = '';
		if (empty($elem['aggr']))
		{
			$field = $fList[$elem['name']];

			if ($field->getEntity()->getName() == 'User')
			{
				if (in_array($elem['name'], array(
					'ASSIGNED_BY.SHORT_NAME'), true))
				{
					$strID = str_replace('.SHORT_NAME', '.ID', $elem['name']);
					$href = array('pattern' => '/company/personal/user/#'.$strID.'#/');
				}
			}
			else if ($field->getEntity()->getName() == 'Deal')
			{
				if (in_array($elem['name'], array(
					'INVOICE_UTS.DEAL_BY.TITLE'), true))
				{
					$href = array('pattern' => '/crm/deal/show/#INVOICE_UTS.DEAL_BY.ID#/');
				}
			}
			else if ($field->getEntity()->getName() == 'Company')
			{
				if (in_array($elem['name'], array(
					'INVOICE_UTS.COMPANY_BY.TITLE'), true))
				{
					$href = array('pattern' => '/crm/company/show/#INVOICE_UTS.COMPANY_BY.ID#/');
				}
			}
			else if ($field->getEntity()->getName() == 'Contact')
			{
				if (in_array($elem['name'], array(
					'INVOICE_UTS.CONTACT_BY.SHORT_NAME'), true))
				{
					$href = array('pattern' => '/crm/contact/show/#INVOICE_UTS.CONTACT_BY.ID#/');
				}
			}
		}

		return $href;
	}
}

class CCrmLeadReportHelper extends CCrmReportHelperBase
{
	protected static function prepareUFInfo()
	{
		if (is_array(self::$arUFId))
			return;

		self::$arUFId = array('CRM_LEAD');
		parent::prepareUFInfo();
	}

	public static function GetReportCurrencyID()
	{
		return CCrmReportManager::GetReportCurrencyID();
	}

	public static function SetReportCurrencyID($currencyID)
	{
		CCrmReportManager::SetReportCurrencyID($currencyID);
	}

	public static function getEntityName()
	{
		return 'Bitrix\Crm\Lead';
	}
	public static function getOwnerId()
	{
		return 'crm_lead';
	}
	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		$columnList = array(
			'ID',
			'TITLE',
			'STATUS_ID',
			'STATUS_DESCRIPTION',
			'STATUS_SUB' => array(
				'IS_WORK',
				'IS_CONVERT',
				'IS_REJECT'
			),
			'OPPORTUNITY',
			'CURRENCY_ID',
			'COMMENTS',
			'SHORT_NAME',
			'NAME',
			'LAST_NAME',
			'SECOND_NAME',
			'BIRTHDATE',
			'COMPANY_TITLE',
			'POST',
			'ADDRESS',
			'PHONE_MOBILE',
			'PHONE_WORK',
			'EMAIL_HOME',
			'EMAIL_WORK',
			'SKYPE',
			'ICQ',
			'SOURCE_ID',
			'SOURCE_DESCRIPTION',
			'DATE_CREATE_SHORT',
			'DATE_MODIFY_SHORT',
			'DATE_CLOSED_SHORT',
			'WEBFORM_ID',
			'IS_RETURN_CUSTOMER_BL'
		);
		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$columnList[] = $fieldId.self::UTM_FIELD_POSTFIX;
		}
		$columnList = array_merge(
			$columnList,
			[
				'ASSIGNED_BY' => array(
					'ID',
					'SHORT_NAME',
					'NAME',
					'LAST_NAME',
					'WORK_POSITION'
				),
				'CREATED_BY' => array(
					'ID',
					'SHORT_NAME',
					'NAME',
					'LAST_NAME',
					'WORK_POSITION'
				),
				'MODIFY_BY' => array(
					'ID',
					'SHORT_NAME',
					'NAME',
					'LAST_NAME',
					'WORK_POSITION'
				),
				'PRODUCT_ROW' => array(
					'ProductRow:LEAD_OWNER.IBLOCK_ELEMENT.ID',
					'ProductRow:LEAD_OWNER.CP_PRODUCT_NAME',
					'ProductRow:LEAD_OWNER.PRICE_ACCOUNT',
					'ProductRow:LEAD_OWNER.QUANTITY',
					'ProductRow:LEAD_OWNER.SUM_ACCOUNT'
				)
			]
		);

		// Append user fields
		$blPostfix = defined('self::UF_BOOLEAN_POSTFIX') ? self::UF_BOOLEAN_POSTFIX : '_BLINL';
		self::prepareUFInfo();
		if (is_array(self::$ufInfo) && count(self::$ufInfo) > 0)
		{
			if (isset(self::$ufInfo['CRM_LEAD']) && is_array(self::$ufInfo['CRM_LEAD'])
				&& count(self::$ufInfo['CRM_LEAD']) > 0)
			{
				foreach (self::$ufInfo['CRM_LEAD'] as $ufKey => $uf)
				{
					if (($uf['USER_TYPE_ID'] !== 'datetime' && $uf['USER_TYPE_ID'] !== 'boolean')
						|| $uf['MULTIPLE'] === 'Y'
						|| mb_substr($ufKey, -mb_strlen(self::UF_DATETIME_SHORT_POSTFIX)) === self::UF_DATETIME_SHORT_POSTFIX
						|| mb_substr($ufKey, -mb_strlen($blPostfix)) === $blPostfix)
					{
						$columnList[] = $ufKey;
					}
				}
			}
		}

		return $columnList;
	}

	public static function getFiltrableColumnGroups()
	{
		return array_merge(parent::getFiltrableColumnGroups(), ['PRODUCT_ROW']);
	}

	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		global $DB;

		$options = array();

		Crm\LeadTable::processQueryOptions($options);

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s IN '.$options['WORK_STATUS_IDS']	.' THEN 1 ELSE 0 END',
				'STATUS_ID'
			),
			'values' => array(0, 1)
		), 'IS_WORK');

		$entity->addField(array(
			'data_type' => 'boolean',
			'expression' => array(
				'CASE WHEN %s IN '.$options['REJECT_STATUS_IDS'].' THEN 1 ELSE 0 END',
				'STATUS_ID'
			),
			'values' => array(0, 1)
		), 'IS_REJECT');

		$datetimeNull = 'CAST(NULL AS DATETIME)';

		$entity->addField(array(
			'data_type' => 'datetime',
			'expression' => array(
				'CASE WHEN %s = \'CONVERTED\' OR %s IN '.$options['REJECT_STATUS_IDS'].' THEN '.$DB->datetimeToDateFunction('%s').' ELSE '.$datetimeNull.' END',
				'STATUS_ID', 'STATUS_ID', 'DATE_CLOSED'
			)
		), 'DATE_CLOSED_SHORT');

		self::appendBooleanUserFieldsIfNull($entity);
		self::appendDateTimeUserFieldsAsShort($entity);
		self::appendMoneyUserFieldsAsSeparated($entity);
		self::appendTextUserFieldsAsTrimmed($entity);
		self::appendUTMFields($entity);
	}

	public static function getCustomSelectFields($select, $fList)
	{
		return [];
	}

	public static function getCustomColumnTypes()
	{
		return array(
			'STATUS_ID' => 'string',
			'CURRENCY_ID' => 'string',
			'SOURCE_ID' => 'string',
			'OPPORTUNITY' => 'float',
			'WEBFORM_ID' => 'string',
			'IS_RETURN_CUSTOMER' => 'boolean',
			'ProductRow:LEAD_OWNER.SUM_ACCOUNT' => 'float',
			'ProductRow:LEAD_OWNER.PRICE_ACCOUNT' => 'float'
		);
	}
	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'TITLE'),
			array('name' => 'DATE_CREATE_SHORT'),
			array('name' => 'STAGE_ID'),
			array('name' => 'ASSIGNED_BY.SHORT_NAME')
		);
	}
	public static function getCalcVariations()
	{
		$calcVariations = [
			'IS_WORK' => array('SUM'),
			'IS_REJECT' => array('SUM'),
			'IS_CONVERT' => array('SUM'),
			'ProductRow:LEAD_OWNER.IBLOCK_ELEMENT.ID' => array('COUNT_DISTINCT', 'GROUP_CONCAT'),
			'ProductRow:LEAD_OWNER.CP_PRODUCT_NAME' => array('COUNT_DISTINCT', 'GROUP_CONCAT')
		];

		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$calcVariations[$fieldId.self::UTM_FIELD_POSTFIX] = ['COUNT_DISTINCT'];
		}

		return array_merge(
			parent::getCalcVariations(),
			$calcVariations
		);
	}
	public static function getCompareVariations()
	{
		return array_merge(
			parent::getCompareVariations(),
			array(
				'STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'TYPE_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'CURRENCY_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'EVENT_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'LEAD_BY' => array(
					'EQUAL'
				),
				'CONTACT_BY' => array(
					'EQUAL'
				),
				'COMPANY_BY' => array(
					'EQUAL'
				),
				'LEAD_BY.STATUS_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'SOURCE_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'WEBFORM_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'CONTACT_BY.TYPE_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'CONTACT_BY.SOURCE_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'COMPANY_BY.INDUSTRY_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'COMPANY_BY.EMPLOYEES_BY.STATUS_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				)
			)
		);
	}
	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options, $runtime);

		if(!isset($select['CRM_LEAD_COMPANY_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_LEAD_COMPANY_BY_') === 0)
				{
					$select['CRM_LEAD_COMPANY_BY_ID'] = 'COMPANY_BY.ID';
					break;
				}
			}
		}

		// HACK: Switch to order by STAGE_BY.SORT instead STAGE_BY.STATUS_ID
		// We are trying to adhere user defined sort rules.
		if(isset($order['STATUS_ID']))
		{
			$select['CRM_LEAD_STATUS_BY_SORT'] = 'STATUS_BY.SORT';
			$order['CRM_LEAD_STATUS_BY_SORT'] = $order['STATUS_ID'];
			unset($order['STATUS_ID']);
		}

		if(!isset($select['CRM_LEAD_CONTACT_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_LEAD_CONTACT_BY_') === 0)
				{
					$select['CRM_LEAD_CONTACT_BY_ID'] = 'CONTACT_BY.ID';
					break;
				}
			}
		}

		if(!isset($select['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_') === 0)
				{
					$select['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_ID'] = 'ProductRow:LEAD_OWNER.IBLOCK_ELEMENT.ID';
					$select['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_IBLOCK_ID'] = 'ProductRow:LEAD_OWNER.IBLOCK_ELEMENT.IBLOCK_ID';
					break;
				}
			}

		}

		// permission
		$addClause = CCrmLead::BuildPermSql('crm_lead');
		if($addClause === false)
		{
			// access dinied
			$filter = array($filter, '=ID' => '0');
		}
		elseif(!empty($addClause))
		{
			global $DB;
			// HACK: add escape chars for ORM
			$addClause = str_replace('crm_lead.ID', $DB->escL.'crm_lead'.$DB->escR.'.ID', $addClause);

			$filter = array($filter,
				'=IS_ALLOWED' => '1'
			);

			$runtime['IS_ALLOWED'] = array(
				'data_type' => 'integer',
				'expression' => array('CASE WHEN '.$addClause.' THEN 1 ELSE 0 END')
			);
		}
	}

	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		// HACK: detect if 'report.view' component is rendering excel spreadsheet
		$isHtml = !(isset($_GET['EXCEL']) && $_GET['EXCEL'] === 'Y');

		$field = $cInfo['field'];
		$fieldName = isset($cInfo['fieldName']) ? $cInfo['fieldName'] : $field->GetName();
		$prcnt = isset($cInfo['prcnt']) ? $cInfo['prcnt'] : '';
		$aggr = (!empty($cInfo['aggr']) && $cInfo['aggr'] !== 'GROUP_CONCAT');

		if(!isset($prcnt[0])
			&& ($fieldName === 'OPPORTUNITY'
				|| $fieldName === 'OPPORTUNITY_ACCOUNT'
				|| $fieldName === 'RECEIVED_AMOUNT'
				|| $fieldName === 'LOST_AMOUNT'
				|| $fieldName === 'ProductRow:LEAD_OWNER.SUM_ACCOUNT'
				|| $fieldName === 'ProductRow:LEAD_OWNER.PRICE_ACCOUNT'
				|| $fieldName === 'COMPANY_BY.REVENUE'))
		{
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'float';
			$customChartValue['value'] = doubleval($v);

			$v = self::MoneyToString(doubleval($v), $isHtml);
		}
		elseif(!$aggr && $fieldName === 'TITLE')
		{
			if($v <> '')
			{
				if ($isHtml && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['ID']))
				{
					$v = self::prepareLeadTitleHtml(self::$CURRENT_RESULT_ROW['ID'], $v);
				}
				else
				{
					$v = htmlspecialcharsbx($v);
				}
			}
		}
		elseif(!$aggr && $fieldName === 'STATUS_ID')
		{
			if($v !== '')
			{
				$v = self::getLeadStatusName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'CURRENCY_ID')
		{
			if($v !== '')
			{
				$v = self::getCurrencyName($v, $isHtml);
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'ASSIGNED_BY.') === 0)
		{
			// unset HREF for empty value
			if (empty($v) || trim($v) === '.' || $v === '&nbsp;')
				unset($row['__HREF_'.$k]);
			if($v == '' || trim($v) === '.')
			{
				$v = GetMessage('CRM_LEAD_RESPONSIBLE_NOT_ASSIGNED');
			}
			elseif($isHtml)
			{
				$v = htmlspecialcharsbx($v);
			}
		}

		elseif(!$aggr && mb_strpos($fieldName, 'ProductRow:LEAD_OWNER.IBLOCK_ELEMENT.') === 0)
		{
			static $defaultCatalogID;
			if(!isset($defaultCatalogID))
			{
				$defaultCatalogID = CCrmCatalog::GetDefaultID();
			}

			if($isHtml)
			{
				if($defaultCatalogID > 0 && self::$CURRENT_RESULT_ROW)
				{
					$iblockID = isset(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_IBLOCK_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_IBLOCK_ID']) : 0;;
					$iblockElementID = isset(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_LEAD_CRM_PRODUCT_ROW_LEAD_OWNER_IBLOCK_ELEMENT_ID']) : 0;
				}
				else
				{
					$iblockID = 0;
					$iblockElementID = 0;
				}

				if($iblockElementID > 0 && $iblockID === $defaultCatalogID)
				{
					$v = self::prepareProductNameHtml($iblockElementID, $v);
				}
				else
				{
					$v = htmlspecialcharsbx($v);
				}
			}
		}
		elseif(!$aggr && $fieldName === 'SOURCE_ID')
		{
			if($v !== '')
			{
				$v = self::getLeadSourceName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'WEBFORM_ID')
		{
			if($v !== '')
			{
				$v = self::getWebFormName($v, $isHtml);
			}
		}
		elseif($fieldName !== 'COMMENTS') // Leave 'COMMENTS' as is for HTML display.
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
		}
	}

	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		parent::formatResultsTotal($total, $columnInfo, $customChartTotal);

		if(isset($total['TOTAL_WEBFORM_ID']))
		{
			// Suppress WEBFORM_ID aggregation
			unset($total['TOTAL_WEBFORM_ID']);
		}
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		if(is_null($date_from) && is_null($date_to))
		{
			return array(); // Empty filter for empty time interval.
		}

		//$now = ConvertTimeStamp(time(), 'FULL');
		$filter = array('LOGIC' => 'AND');
		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=DATE_CREATE_SHORT' => $date_to,
				'=DATE_CREATE_SHORT' => null
			);
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=DATE_CLOSED_SHORT' => $date_from,
				'=DATE_CLOSED_SHORT' => null
			);
		}

		return $filter;
	}

	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'14.1.0' => array(
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_DESCR'),
					'mark_default' => 1,
					'settings' => unserialize('a:10:{s:6:"entity";s:15:"Bitrix\Crm\Lead";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:8:{i:3;a:2:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";s:5:"alias";s:0:"";}i:4;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:5;a:4:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";s:5:"prcnt";s:11:"self_column";}i:6;a:3:{s:4:"name";s:7:"IS_WORK";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:7;a:3:{s:4:"name";s:10:"IS_CONVERT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:4:{s:4:"name";s:10:"IS_CONVERT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:11:"self_column";}i:9;a:3:{s:4:"name";s:9:"IS_REJECT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:10;a:4:{s:4:"name";s:9:"IS_REJECT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:11:"self_column";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:9:"SOURCE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:3;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:3;s:9:"y_columns";a:3:{i:0;i:6;i:1;i:9;i:2;i:7;}}}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_STATUS'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_STATUS_DESCR'),
					'mark_default' => 2,
					'settings' => unserialize('a:10:{s:6:"entity";s:15:"Bitrix\Crm\Lead";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:5:{i:4;a:1:{s:4:"name";s:9:"STATUS_ID";}i:5;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:6;a:4:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";s:5:"prcnt";s:11:"self_column";}i:7;a:3:{s:4:"name";s:11:"OPPORTUNITY";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:4:{s:4:"name";s:11:"OPPORTUNITY";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:11:"self_column";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:9:"SOURCE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:4;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:4;s:9:"y_columns";a:1:{i:0;i:5;}}}', ['allowed_classes' => false])
				),
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_DESCR'),
					'mark_default' => 3,
					'settings' => unserialize('a:10:{s:6:"entity";s:15:"Bitrix\Crm\Lead";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:4;a:2:{s:4:"name";s:9:"SOURCE_ID";s:5:"alias";s:0:"";}i:5;a:3:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:6;a:4:{s:4:"name";s:2:"ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";s:5:"prcnt";s:11:"self_column";}i:7;a:3:{s:4:"name";s:10:"IS_CONVERT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:4:{s:4:"name";s:10:"IS_CONVERT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:11:"self_column";}i:9;a:3:{s:4:"name";s:9:"IS_REJECT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:10;a:4:{s:4:"name";s:9:"IS_REJECT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";s:5:"prcnt";s:11:"self_column";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:9:"SOURCE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:5;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"pie";s:8:"x_column";i:4;s:9:"y_columns";a:1:{i:0;i:5;}}}', ['allowed_classes' => false])
				)
			)
		);

		foreach ($reports as &$reportByVersion)
		{
			foreach ($reportByVersion as &$report)
			{
				if ($report['mark_default'] === 1)
				{
					$report['settings']['select'][3]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_3');
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_4');
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_6');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_7');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_8');
					$report['settings']['select'][9]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_9');
					$report['settings']['select'][10]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_MANAGER_ALIAS_10');
				}
				else if ($report['mark_default'] === 2)
				{
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_STATUS_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_STATUS_ALIAS_6');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_STATUS_ALIAS_7');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_STATUS_ALIAS_8');
				}
				else if ($report['mark_default'] === 3)
				{
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_ALIAS_4');
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_ALIAS_6');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_ALIAS_7');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_ALIAS_8');
					$report['settings']['select'][9]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_ALIAS_9');
					$report['settings']['select'][10]['alias'] = GetMessage('CRM_REPORT_DEFAULT_LEADS_BY_SOURCE_ALIAS_10');
				}
			}
			unset($report);
		}
		unset($reportByVersion);

		return $reports;
	}

	public static function getFirstVersion()
	{
		return '14.0.0';
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		$href = '';
		if (empty($elem['aggr']))
		{
			$field = $fList[$elem['name']];

			if ($field->getEntity()->getName() == 'User')
			{
				if (in_array($elem['name'], array(
					'ASSIGNED_BY.SHORT_NAME',
					'CREATED_BY.SHORT_NAME',
					'MODIFY_BY.SHORT_NAME'), true))
				{
					$strID = str_replace('.SHORT_NAME', '.ID', $elem['name']);
					$href = array('pattern' => '/company/personal/user/#'.$strID.'#/');
				}
			}
		}

		return $href;
	}
}

class CCrmActivityReportHelper extends CCrmReportHelperBase
{
	public static function GetReportCurrencyID()
	{
		return CCrmReportManager::GetReportCurrencyID();
	}

	public static function SetReportCurrencyID($currencyID)
	{
		CCrmReportManager::SetReportCurrencyID($currencyID);
	}

	public static function getEntityName()
	{
		return 'Bitrix\Crm\Activity';
	}
	public static function getOwnerId()
	{
		return 'crm_activity';
	}
	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		return array(
			'ID',
			'TYPE_ID',
			'DIRECTION',
			'TYPE_SUB' => array(
				'IS_MEETING',
				'IS_CALL',
				'IS_CALL_IN',
				'IS_CALL_OUT',
				'IS_TASK',
				'IS_EMAIL',
				'IS_EMAIL_IN',
				'IS_EMAIL_OUT'
			),
			'SUBJECT',
			'COMPLETED',
			'PRIORITY',
			'LOCATION',
			'DATE_CREATED_SHORT',
			'LAST_UPDATED_SHORT',
			'DATE_FINISHED_SHORT',
			'START_TIME_SHORT',
			'END_TIME_SHORT',
			'ASSIGNED_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'AUTHOR_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			),
			'EDITOR_BY' => array(
				'ID',
				'SHORT_NAME',
				'NAME',
				'LAST_NAME',
				'WORK_POSITION'
			)
		);
	}
	public static function getCustomColumnTypes()
	{
		return array(
			'TYPE_ID' => 'string',
			'DIRECTION' => 'string',
			'PRIORITY' => 'string'
		);
	}
	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'ID'),
			array('name' => 'DATE_CREATED_SHORT'),
			array('name' => 'TYPE_ID'),
			array('name' => 'DIRECTION'),
			array('name' => 'PRIORITY'),
			array('name' => 'END_TIME_SHORT'),
			array('name' => 'ASSIGNED_BY.SHORT_NAME')
		);
	}
	public static function getCalcVariations()
	{
		return array_merge(
			parent::getCalcVariations(),
			array(
				'IS_MEETING' => array('SUM'),
				'IS_CALL' => array('SUM'),
				'IS_CALL_IN' => array('SUM'),
				'IS_CALL_OUT' => array('SUM'),
				'IS_TASK' => array('SUM'),
				'IS_EMAIL' => array('SUM'),
				'IS_EMAIL_IN' => array('SUM'),
				'IS_EMAIL_OUT' => array('SUM')
			)
		);
	}
	public static function getCompareVariations()
	{
		return array_merge(
			parent::getCompareVariations(),
			array(
				'TYPE_ID' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'DIRECTION' => array(
					'EQUAL',
					'NOT_EQUAL'
				),
				'PRIORITY' => array(
					'EQUAL',
					'NOT_EQUAL'
				)
			)
		);
	}
	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options, $runtime);
		static::rewriteTaskActivityFilter($filter);
		// Dynamic data setup
		//Crm\ActivityTable::ProcessQueryOptions($options);

		if(!isset($select['CRM_ACTIVITY_COMPANY_BY_ID']))
		{
			foreach($select as $k => $v)
			{
				if(mb_strpos($k, 'CRM_ACTIVITY_COMPANY_BY_') === 0)
				{
					$select['CRM_ACTIVITY_COMPANY_BY_ID'] = 'COMPANY_BY.ID';
					break;
				}
			}
		}

		// permission
		$addClause = CCrmActivity::BuildPermSql('crm_activity');
		if($addClause === false)
		{
			// access dinied
			$filter = array($filter, '=ID' => '0');
		}
		elseif(!empty($addClause))
		{
			global $DB;
			// HACK: add escape chars for ORM
			$addClause = str_replace('crm_activity.ID', $DB->escL.'crm_activity'.$DB->escR.'.ID', $addClause);

			$filter = array($filter,
				'=IS_ALLOWED' => '1'
			);

			$runtime['IS_ALLOWED'] = array(
				'data_type' => 'integer',
				'expression' => array('CASE WHEN '.$addClause.' THEN 1 ELSE 0 END')
			);
		}
	}

	private static function rewriteTaskActivityFilter(array &$filter): void
	{
		foreach ($filter as $key => &$value)
		{
			if ($key === '=TYPE_ID' && (int)$value === CCrmActivityType::Task)
			{
				$filter['LOGIC'] = 'OR';
				$filter['=PROVIDER_ID'] = Crm\Activity\Provider\Tasks\Task::getId();
				break;
			}
			elseif (is_array($value))
			{
				self::rewriteTaskActivityFilter($value);
			}
		}
	}


	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		// HACK: detect if 'report.view' component is rendering excel spreadsheet
		$isHtml = !(isset($_GET['EXCEL']) && $_GET['EXCEL'] === 'Y');

		$field = $cInfo['field'];
		$fieldName = isset($cInfo['fieldName']) ? $cInfo['fieldName'] : $field->GetName();
		$prcnt = isset($cInfo['prcnt']) ? $cInfo['prcnt'] : '';
		$aggr = (!empty($cInfo['aggr']) && $cInfo['aggr'] !== 'GROUP_CONCAT');

		if (!$aggr && $fieldName === 'TYPE_ID')
		{
			if ($v !== '')
			{
				// nasty... I have no choice...
				if (static::isTaskActivity((int)$row['ID'], (int)$row['TYPE_ID']))
				{
					$v = Crm\Activity\Provider\Tasks\Task::getName();
				}
				else
				{
					$v = self::getActivityTypeName($v, $isHtml);
				}
			}
		}
		elseif (!$aggr && $fieldName === 'DIRECTION')
		{
			if ($v !== '')
			{
				$v = self::getActivityDirectionName($v, 0, $isHtml);
			}
		}
		elseif (!$aggr && $fieldName === 'PRIORITY')
		{
			if ($v !== '')
			{
				$v = self::getActivityPriorityName($v, $isHtml);
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'ASSIGNED_BY.') === 0
				|| mb_strpos($fieldName, 'AUTHOR_BY.') === 0
				|| mb_strpos($fieldName, 'EDITOR_BY.') === 0)
		{
			// unset HREF for empty value
			if (empty($v) || trim($v) === '.' || $v === '&nbsp;')
				unset($row['__HREF_'.$k]);
			if(($v == '' || trim($v) === '.') && mb_strpos($fieldName, '.WORK_POSITION') !== mb_strlen($fieldName) - 14)
			{
				$v = GetMessage('CRM_ACTIVITY_RESPONSIBLE_NOT_ASSIGNED');
			}
			if($isHtml)
			{
				$v = htmlspecialcharsbx($v);
			}
		}
		elseif ($fieldName !== 'SUBJECT') // Leave 'SUBJECT' as is for HTML display.
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
		}
	}

	public static function getPeriodFilter($date_from, $date_to)
	{
		if(is_null($date_from) && is_null($date_to))
		{
			return array();
		}

		$filter = array('LOGIC' => 'AND');
		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=DATE_CREATED_SHORT' => $date_to/*,
				'=DATE_CREATED_SHORT' => null*/
			);
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=END_TIME_SHORT' => $date_from
				/*'>=DATE_FINISHED_SHORT' => $date_from//,
				//'=DATE_FINISHED_SHORT' => null*/
			);
		}

		return $filter;
	}

	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		parent::formatResultsTotal($total, $columnInfo);

		// Suppress total values
		if(isset($total['TOTAL_TYPE_ID']))
		{
			unset($total['TOTAL_TYPE_ID']);
		}
		if(isset($total['TOTAL_DIRECTION']))
		{
			unset($total['TOTAL_DIRECTION']);
		}
		if(isset($total['TOTAL_PRIORITY']))
		{
			unset($total['TOTAL_PRIORITY']);
		}
	}

	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'14.1.0' => array(
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER_DESCR'),
					'mark_default' => 1,
					'settings' => unserialize('a:10:{s:6:"entity";s:19:"Bitrix\Crm\Activity";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:7:{i:2;a:1:{s:4:"name";s:22:"ASSIGNED_BY.SHORT_NAME";}i:3;a:3:{s:4:"name";s:10:"IS_CALL_IN";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:4;a:3:{s:4:"name";s:11:"IS_CALL_OUT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:5;a:3:{s:4:"name";s:10:"IS_MEETING";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:6;a:3:{s:4:"name";s:11:"IS_EMAIL_IN";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:7;a:3:{s:4:"name";s:12:"IS_EMAIL_OUT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:8;a:3:{s:4:"name";s:7:"IS_TASK";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:3:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:9:"COMPLETED";s:7:"compare";s:5:"EQUAL";s:5:"value";s:4:"true";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:11:"ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:2;s:9:"sort_type";s:3:"ASC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"bar";s:8:"x_column";i:2;s:9:"y_columns";a:6:{i:0;i:3;i:1;i:4;i:2;i:5;i:3;i:6;i:4;i:7;i:5;i:8;}}}', ['allowed_classes' => false])
				)
			)
		);

		foreach ($reports as &$reportByVersion)
		{
			foreach ($reportByVersion as &$report)
			{
				if ($report['mark_default'] === 1)
				{
					$report['settings']['select'][3]['alias'] = GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER_ALIAS_3');
					$report['settings']['select'][4]['alias'] = GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER_ALIAS_4');
					$report['settings']['select'][5]['alias'] = GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER_ALIAS_5');
					$report['settings']['select'][6]['alias'] = GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER_ALIAS_6');
					$report['settings']['select'][7]['alias'] = GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER_ALIAS_7');
					$report['settings']['select'][8]['alias'] = GetMessage('CRM_REPORT_DEFAULT_ACTIVITIES_BY_MANAGER_ALIAS_8');
				}
			}
			unset($report);
		}
		unset($reportByVersion);

		return $reports;
	}

	public static function getFirstVersion()
	{
		return '14.0.0';
	}

	public static function getDefaultElemHref($elem, $fList)
	{
		$href = '';
		if (empty($elem['aggr']))
		{
			$field = $fList[$elem['name']];

			if ($field->getEntity()->getName() == 'User')
			{
				if (in_array($elem['name'], array(
					'ASSIGNED_BY.SHORT_NAME',
					'AUTHOR_BY.SHORT_NAME',
					'EDITOR_BY.SHORT_NAME'), true))
				{
					$strID = str_replace('.SHORT_NAME', '.ID', $elem['name']);
					$href = array('pattern' => '/company/personal/user/#'.$strID.'#/');
				}
			}
		}

		return $href;
	}

	private static function isTaskActivity(int $activityId, int $typeId): bool
	{
		if ($activityId <= 0)
		{
			return false;
		}

		if ($typeId !== CCrmActivityType::Provider)
		{
			return false;
		}

		$query = Crm\ActivityTable::query();
		$query
			->setSelect(['ID', 'PROVIDER_ID'])
			->where('ID', $activityId);

		$activity = $query->exec()->fetchObject();
		if (is_null($activity))
		{
			return false;
		}

		return $activity->getProviderId() === Crm\Activity\Provider\Tasks\Task::getId();
	}
}

class CCrmProductReportHelper extends CCrmReportHelperBase
{
	public static function GetReportCurrencyID()
	{
		return CCrmReportManager::GetReportCurrencyID();
	}

	public static function SetReportCurrencyID($currencyID)
	{
		CCrmReportManager::SetReportCurrencyID($currencyID);
	}

	public static function getEntityName()
	{
		return 'Bitrix\Crm\ProductRow';
	}
	public static function getOwnerId()
	{
		return 'crm_product_row';
	}
	public static function getDefaultColumns()
	{
		return array(
			array('name' => 'CP_PRODUCT_NAME')
		);
	}
	public static function getCalcVariations()
	{
		$calcVariations = [];

		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$calcVariations['DEAL_OWNER.'.$fieldId.self::UTM_FIELD_POSTFIX] = ['COUNT_DISTINCT'];
		}

		return array_merge(
			parent::getCalcVariations(),
			$calcVariations
		);
	}
	public static function getColumnList()
	{
		IncludeModuleLangFile(__FILE__);

		$columnList = [
			'CP_PRODUCT_NAME',
			'PRICE_ACCOUNT',
			'QUANTITY',
			'SUM_ACCOUNT',
			'DEAL_OWNER' => [
				'ID',
				'TITLE',
				'COMMENTS',
				'STAGE_ID',
				'CLOSED',
				'TYPE_ID',
				'PROBABILITY',
				'OPPORTUNITY_ACCOUNT',
				'BEGINDATE',
				'CLOSEDATE',
				'ASSIGNED_BY' => array(
					'ID',
					'SHORT_NAME',
					'NAME',
					'LAST_NAME',
					'WORK_POSITION'
				),
				'DATE_CREATE',
				'CREATED_BY' => array(
					'ID',
					'SHORT_NAME',
					'NAME',
					'LAST_NAME',
					'WORK_POSITION'
				),
				'DATE_MODIFY',
				'MODIFY_BY' => array(
					'ID',
					'SHORT_NAME',
					'NAME',
					'LAST_NAME',
					'WORK_POSITION'
				),
				'WEBFORM_ID',
				'IS_RETURN_CUSTOMER_BL'
			]
		];
		foreach (array_keys(static::getUTMFieldMap()) as $fieldId)
		{
			$columnList['DEAL_OWNER'][] = $fieldId.self::UTM_FIELD_POSTFIX;
		}
		$columnList['DEAL_OWNER'] = array_merge(
			$columnList['DEAL_OWNER'],
			[
				'CONTACT_BY' => array(
					'ID',
					'NAME',
					'LAST_NAME',
					'SECOND_NAME',
					'BIRTHDATE',
					'POST',
					'ADDRESS',
					'TYPE_BY.STATUS_ID',
					'COMMENTS',
					'SOURCE_BY.STATUS_ID',
					'SOURCE_DESCRIPTION',
					'DATE_CREATE',
					'DATE_MODIFY',
					'ASSIGNED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'CREATED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'MODIFY_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					)
				),
				'COMPANY_BY' => array(
					'ID',
					'TITLE',
					'COMPANY_TYPE_BY.STATUS_ID',
					'INDUSTRY_BY.STATUS_ID',
					'EMPLOYEES_BY.STATUS_ID',
					'REVENUE',
					'CURRENCY_ID',
					'COMMENTS',
					'ADDRESS',
					'ADDRESS_LEGAL',
					'BANKING_DETAILS',
					'DATE_CREATE',
					'DATE_MODIFY',
					'CREATED_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					),
					'MODIFY_BY' => array(
						'ID',
						'SHORT_NAME',
						'NAME',
						'LAST_NAME',
						'WORK_POSITION'
					)
				),
				'ORIGINATOR_BY.ID'
			]
		);

		return $columnList;
	}
	public static function getCustomColumnTypes()
	{
		return array(
			'DEAL_OWNER.WEBFORM_ID' => 'string'
		);
	}
	public static function setRuntimeFields(\Bitrix\Main\Entity\Base $entity, $sqlTimeInterval)
	{
		self::appendUTMFields($entity);
	}
	public static function getPeriodFilter($date_from, $date_to)
	{
		if(is_null($date_from) && is_null($date_to))
		{
			return array(); // Empty filter for empty time interval.
		}

		$filter = array('LOGIC' => 'AND');
		if(!is_null($date_to))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'<=DEAL_OWNER.BEGINDATE' => $date_to,
				'=DEAL_OWNER.BEGINDATE' => null
			);
		}

		if(!is_null($date_from))
		{
			$filter[] = array(
				'LOGIC' => 'OR',
				'>=DEAL_OWNER.CLOSEDATE' => $date_from,
				'=DEAL_OWNER.CLOSEDATE' => null
			);
		}

		return $filter;
	}
	public static function getCompareVariations()
	{
		return array_merge(
			parent::getCompareVariations(),
			[
				'DEAL_OWNER' => [
					'EQUAL'
				],
				'DEAL_OWNER.STAGE_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.TYPE_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.CURRENCY_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.EVENT_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.WEBFORM_ID' =>[
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.LEAD_BY' => [
					'EQUAL'
				],
				'DEAL_OWNER.CONTACT_BY' => [
					'EQUAL'
				],
				'DEAL_OWNER.COMPANY_BY' => [
					'EQUAL'
				],
				'DEAL_OWNER.LEAD_BY.STATUS_BY.STATUS_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.CONTACT_BY.TYPE_BY.STATUS_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.CONTACT_BY.SOURCE_BY.STATUS_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.COMPANY_BY.INDUSTRY_BY.STATUS_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				],
				'DEAL_OWNER.COMPANY_BY.EMPLOYEES_BY.STATUS_ID' => [
					'EQUAL',
					'NOT_EQUAL'
				]
			]
		);
	}
	public static function beforeViewDataQuery(&$select, &$filter, &$group, &$order, &$limit, &$options, &$runtime = null)
	{
		parent::beforeViewDataQuery($select, $filter, $group, $order, $limit, $options, $runtime);

		// permission
		$addClause = CCrmDeal::BuildPermSql('crm_product_row_deal_owner');
		if($addClause === false)
		{
			// access dinied
			$filter = array($filter, '=OWNER_ID' => '0');
		}
		elseif(!empty($addClause))
		{
			global $DB;
			// HACK: add escape chars for ORM
			$addClause = str_replace('crm_product_row_deal_owner.ID', '`crm_product_row`.`OWNER_ID`', $addClause);

			$filter = array($filter,
				'=IS_ALLOWED' => '1'
			);

			$runtime['IS_ALLOWED'] = array(
				'data_type' => 'integer',
				'expression' => array('CASE WHEN '.$addClause.' THEN 1 ELSE 0 END')
			);
		}

		if(!isset($select['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_ID']))
		{
			$select['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_ID'] = 'IBLOCK_ELEMENT.ID';
		}

		if(!isset($select['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_IBLOCK_ID']))
		{
			$select['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_IBLOCK_ID'] = 'IBLOCK_ELEMENT.IBLOCK_ID';
		}
	}
	public static function formatResultValue($k, &$v, &$row, &$cInfo, $total, &$customChartValue = null)
	{
		// HACK: detect if 'report.view' component is rendering excel spreadsheet
		$isHtml = !(isset($_GET['EXCEL']) && $_GET['EXCEL'] === 'Y');

		$field = $cInfo['field'];
		$fieldName = isset($cInfo['fieldName']) ? $cInfo['fieldName'] : $field->GetName();
		$prcnt = isset($cInfo['prcnt']) ? $cInfo['prcnt'] : '';
		$aggr = (!empty($cInfo['aggr']) && $cInfo['aggr'] !== 'GROUP_CONCAT');

		if(!isset($prcnt[0])
			&& ($fieldName === 'DEAL_OWNER.OPPORTUNITY'
				|| $fieldName === 'DEAL_OWNER.OPPORTUNITY_ACCOUNT'
				|| $fieldName === 'DEAL_OWNER.RECEIVED_AMOUNT'
				|| $fieldName === 'DEAL_OWNER.LOST_AMOUNT'
				|| $fieldName === 'SUM_ACCOUNT'
				|| $fieldName === 'PRICE_ACCOUNT'
				|| $fieldName === 'DEAL_OWNER.COMPANY_BY.REVENUE'))
		{
			// unformatted value for charts
			$customChartValue['exist'] = true;
			$customChartValue['type'] = 'float';
			$customChartValue['value'] = doubleval($v);

			$v = self::MoneyToString(doubleval($v), $isHtml);
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.TITLE')
		{
			if($isHtml && $v <> '' && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_DEAL_OWNER_ID']))
			{
				$v = self::prepareDealTitleHtml(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_DEAL_OWNER_ID'], $v);
			}
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.STAGE_ID')
		{
			if($v !== '')
			{
				$v = self::getDealStageName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.TYPE_ID')
		{
			if($v !== '')
			{
				$v = self::getDealTypeName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.CURRENCY_ID' || $fieldName === 'DEAL_OWNER.COMPANY_BY.CURRENCY_ID')
		{
			if($v !== '')
			{
				$v = self::getCurrencyName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.EVENT_ID')
		{
			if($v !== '')
			{
				$v = self::getEventTypeName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.WEBFORM_ID')
		{
			if($v !== '')
			{
				$v = self::getWebFormName($v, $isHtml);
			}
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.ORIGINATOR_BY.ID')
		{
			$v = self::getDealOriginatorName($v, $isHtml);
		}
		elseif(!$aggr && $fieldName === 'DEAL_OWNER.CONTACT_BY.SOURCE_BY.STATUS_ID')
		{
			if($v !== '')
			{
				$v = self::getStatusName($v, 'SOURCE', $isHtml);
			}
		}
		elseif(mb_strpos($fieldName, 'DEAL_OWNER.COMPANY_BY.') === 0)
		{
			if(!$aggr && ($v == '' || trim($v) === '.'))
			{
				if(mb_strpos($fieldName, 'DEAL_OWNER.COMPANY_BY.COMPANY_TYPE_BY') !== 0
					&& mb_strpos($fieldName, 'DEAL_OWNER.COMPANY_BY.INDUSTRY_BY') !== 0
					&& mb_strpos($fieldName, 'DEAL_OWNER.COMPANY_BY.EMPLOYEES_BY') !== 0)
				{
					$v = GetMessage('CRM_DEAL_COMPANY_NOT_ASSIGNED');
				}
			}
			elseif(!$aggr && $fieldName === 'DEAL_OWNER.COMPANY_BY.TITLE')
			{
				if($isHtml && self::$CURRENT_RESULT_ROW && isset(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_DEAL_OWNER_COMPANY_BY_ID']))
				{
					$v = self::prepareCompanyTitleHtml(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_DEAL_OWNER_COMPANY_BY_ID'], $v);
				}
			}
			elseif(!$aggr && $fieldName === 'DEAL_OWNER.COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'COMPANY_TYPE', $isHtml);
				}
			}
			elseif(!$aggr && $fieldName === 'DEAL_OWNER.COMPANY_BY.INDUSTRY_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'INDUSTRY', $isHtml);
				}
			}
			elseif(!$aggr && $fieldName === 'DEAL_OWNER.COMPANY_BY.EMPLOYEES_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'EMPLOYEES', $isHtml);
				}
			}
		}
		elseif(mb_strpos($fieldName, 'DEAL_OWNER.CONTACT_BY.') === 0)
		{
			if(!$aggr && ($v === '' || trim($v) === '.'))
			{
				if(mb_strpos($fieldName, 'DEAL_OWNER.CONTACT_BY.TYPE_BY') !== 0)
				{
					$v = GetMessage('CRM_DEAL_CONTACT_NOT_ASSIGNED');
				}
			}
			elseif(!$aggr && $fieldName === 'DEAL_OWNER.CONTACT_BY.TYPE_BY.STATUS_ID')
			{
				if($v !== '')
				{
					$v = self::getStatusName($v, 'CONTACT_TYPE', $isHtml);
				}
			}
			elseif(!$aggr && ($fieldName === 'DEAL_OWNER.CONTACT_BY.NAME'
					|| $fieldName === 'DEAL_OWNER.CONTACT_BY.LAST_NAME'
					|| $fieldName === 'DEAL_OWNER.CONTACT_BY.SECOND_NAME'
					|| $fieldName === 'DEAL_OWNER.CONTACT_BY.ADDRESS'))
			{
				if($isHtml && self::$CURRENT_RESULT_ROW
					&& isset(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_DEAL_OWNER_CONTACT_BY_ID']))
				{
					$v = self::prepareContactTitleHtml(
						self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_DEAL_OWNER_CONTACT_BY_ID'],
						$v
					);
				}
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'DEAL_OWNER.ASSIGNED_BY.') === 0)
		{
			// unset HREF for empty value
			if (empty($v) || trim($v) === '.' || $v === '&nbsp;')
				unset($row['__HREF_'.$k]);
			if($v == '' || trim($v) === '.')
			{
				$v = GetMessage('CRM_DEAL_RESPONSIBLE_NOT_ASSIGNED');
			}
			elseif($isHtml)
			{
				$v = htmlspecialcharsbx($v);
			}
		}
		elseif(!$aggr && mb_strpos($fieldName, 'CP_PRODUCT_NAME') === 0)
		{
			static $defaultCatalogID;
			if(!isset($defaultCatalogID))
			{
				$defaultCatalogID = CCrmCatalog::GetDefaultID();
			}

			if($isHtml)
			{
				if($defaultCatalogID > 0 && self::$CURRENT_RESULT_ROW)
				{
					$iblockID = isset(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_IBLOCK_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_IBLOCK_ID']) : 0;;
					$iblockElementID = isset(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_ID'])
						? intval(self::$CURRENT_RESULT_ROW['CRM_PRODUCT_ROW_IBLOCK_ELEMENT_ID']) : 0;
				}
				else
				{
					$iblockID = 0;
					$iblockElementID = 0;
				}

				if($iblockElementID > 0 && $iblockID === $defaultCatalogID)
				{
					$v = self::prepareProductNameHtml($iblockElementID, $v);
				}
				else
				{
					$v = htmlspecialcharsbx($v);
				}
			}
		}
		else
		{
			parent::formatResultValue($k, $v, $row, $cInfo, $total, $customChartValue);
		}
	}
	public static function formatResultsTotal(&$total, &$columnInfo, &$customChartTotal = null)
	{
		parent::formatResultsTotal($total, $columnInfo);
		if(isset($total['TOTAL_CRM_PRODUCT_ROW_DEAL_OWNER_PROBABILITY']))
		{
			// Suppress PROBABILITY (%) aggregation
			unset($total['TOTAL_CRM_PRODUCT_ROW_DEAL_OWNER_PROBABILITY']);
		}
		if(isset($total['TOTAL_CRM_PRODUCT_ROW_DEAL_OWNER_WEBFORM_ID']))
		{
			// Suppress WEBFORM_ID aggregation
			unset($total['TOTAL_CRM_PRODUCT_ROW_DEAL_OWNER_WEBFORM_ID']);
		}
	}
	public static function getDefaultReports()
	{
		IncludeModuleLangFile(__FILE__);

		$reports = array(
			'12.0.9' => array(
				array(
					'title' => GetMessage('CRM_REPORT_DEFAULT_PRODUCTS_PROFIT'),
					'description' => GetMessage('CRM_REPORT_DEFAULT_PRODUCTS_PROFIT_DESCR'),
					'mark_default' => 1,
					'settings' => unserialize('a:10:{s:6:"entity";s:21:"Bitrix\Crm\ProductRow";s:6:"period";a:2:{s:4:"type";s:5:"month";s:5:"value";N;}s:6:"select";a:4:{i:0;a:2:{s:4:"name";s:15:"CP_PRODUCT_NAME";s:5:"alias";s:0:"";}i:1;a:3:{s:4:"name";s:13:"DEAL_OWNER.ID";s:5:"alias";s:0:"";s:4:"aggr";s:14:"COUNT_DISTINCT";}i:2;a:3:{s:4:"name";s:8:"QUANTITY";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}i:3;a:3:{s:4:"name";s:11:"SUM_ACCOUNT";s:5:"alias";s:0:"";s:4:"aggr";s:3:"SUM";}}s:6:"filter";a:1:{i:0;a:9:{i:0;a:5:{s:4:"type";s:5:"field";s:4:"name";s:13:"DEAL_OWNER.ID";s:7:"compare";s:7:"GREATER";s:5:"value";s:1:"0";s:10:"changeable";s:1:"0";}i:1;a:5:{s:4:"type";s:5:"field";s:4:"name";s:19:"DEAL_OWNER.STAGE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:3:"WON";s:10:"changeable";s:1:"1";}i:2;a:5:{s:4:"type";s:5:"field";s:4:"name";s:18:"DEAL_OWNER.TYPE_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:3;a:5:{s:4:"type";s:5:"field";s:4:"name";s:47:"DEAL_OWNER.COMPANY_BY.COMPANY_TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:4;a:5:{s:4:"type";s:5:"field";s:4:"name";s:21:"DEAL_OWNER.COMPANY_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:5;a:5:{s:4:"type";s:5:"field";s:4:"name";s:39:"DEAL_OWNER.CONTACT_BY.TYPE_BY.STATUS_ID";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:6;a:5:{s:4:"type";s:5:"field";s:4:"name";s:21:"DEAL_OWNER.CONTACT_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}i:7;a:5:{s:4:"type";s:5:"field";s:4:"name";s:22:"DEAL_OWNER.ASSIGNED_BY";s:7:"compare";s:5:"EQUAL";s:5:"value";s:0:"";s:10:"changeable";s:1:"1";}s:5:"LOGIC";s:3:"AND";}}s:4:"sort";i:3;s:9:"sort_type";s:4:"DESC";s:5:"limit";N;s:12:"red_neg_vals";b:0;s:13:"grouping_mode";b:0;s:5:"chart";a:4:{s:7:"display";b:1;s:4:"type";s:3:"pie";s:8:"x_column";i:0;s:9:"y_columns";a:1:{i:0;i:3;}}}', ['allowed_classes' => false])
				)
			)
		);

		foreach ($reports as &$reportByVersion)
		{
			foreach ($reportByVersion as &$report)
			{
				if ($report['mark_default'] === 1)
				{
					$report['settings']['select'][0]['alias'] = GetMessage('CRM_REPORT_ALIAS_PRODUCT');
					$report['settings']['select'][1]['alias'] = GetMessage('CRM_REPORT_ALIAS_DEALS_QUANTITY');
					$report['settings']['select'][2]['alias'] = GetMessage('CRM_REPORT_ALIAS_SOLD_PRODUCTS_QUANTITY');
					$report['settings']['select'][3]['alias'] = GetMessage('CRM_REPORT_ALIAS_SALES_PROFIT');
				}
			}
			unset($report);
		}
		unset($reportByVersion);

		return $reports;
	}
	public static function getFirstVersion()
	{
		return '12.0.9';
	}
}