<?php

use Bitrix\Crm\CompanyAddress;
use Bitrix\Crm\ContactAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\LeadAddress;

abstract class CCrmTemplateMapperBase
{
	protected $contentType = CCrmContentType::PlainText;
	protected $debugMode = false;

	public function GetContentType()
	{
		return $this->contentType;
	}
	public function SetContentType($type)
	{
		$this->contentType = $type;
	}

	public function IsDebugMode()
	{
		return $this->debugMode;
	}
	public function EnableDebugMode($enable)
	{
		$this->debugMode = $enable;
	}
	abstract public function MapPath($path);
}

class CCrmTemplateMapper extends CCrmTemplateMapperBase
{
	private static $LEAD_STATUSES = null;
	private static $QUOTE_STATUSES = null;
	private static $SOURCES = null;
	private static $DEAL_TYPES = null;
	private static $DEAL_STAGES = null;
	private static $CONTACT_TYPES = null;
	private static $COMPANY_TYPES = null;
	private static $INDUSTRIES = null;
	private static $EMPLOYEES = null;

	private $context = null;
	function __construct($typeID, $ID)
	{
		$this->context = self::ResolveEntityInfo($typeID, $ID);
	}
	public function MapPath($path)
	{
		$path = strval($path);
		if($path === '')
		{
			return '';
		}

		if($this->context === null)
		{
			return $this->debugMode ? $path : '';
		}

		$path = preg_replace('/\s*\(.*\)\s*$/', '', $path);

		$typeName = isset($this->context['TYPE_NAME']) ? $this->context['TYPE_NAME'] : '';
		$parts = explode('.', $path);
		if(count($parts) < 2 || $typeName === '' || $typeName !== $parts[0])
		{
			//Invalid path or invalid info is specified
			return $path;
		}

		// Take 3 (max depth) from 2 (fisrt is context entity type name)
		$parts = array_slice($parts, 1, 3);

		$result = '';
		$curEntityInfo = &$this->context;
		foreach($parts as &$part)
		{
			if(isset($curEntityInfo['ASSOCIATIONS']) && isset($curEntityInfo['ASSOCIATIONS'][$part]))
			{
				$curEntityInfo = &$curEntityInfo['ASSOCIATIONS'][$part];
				continue;
			}

			$curResult = $this->MapField($curEntityInfo, $part);
			if(is_array($curResult))
			{
				if(!isset($curEntityInfo['ASSOCIATIONS']))
				{
					$curEntityInfo['ASSOCIATIONS'] = array();
				}
				$curEntityInfo['ASSOCIATIONS'][$part] = &$curResult;
				$curEntityInfo = &$curResult;
				unset($curResult);
				continue;
			}

			$result = $curResult;
			break;
		}
		unset($part, $curEntityInfo);

		if($this->debugMode && $result === '')
		{
			$result =  $path;
		}
		return $result;
	}
	private static function ResolveEntityInfo($typeID, $ID)
	{
		global $USER_FIELD_MANAGER;

		$typeID = intval($typeID);
		$ID = intval($ID);

		$entityInfo = array(
			'TYPE_ID'   => $typeID,
			'TYPE_NAME' => \CCrmOwnerType::System == $typeID ? 'SENDER' : \CCrmOwnerType::resolveName($typeID),
			'ID'        => $ID,
		);

		if (!(\CCrmOwnerType::isDefined($typeID) && $ID > 0))
			return $entityInfo;

		$entityClasses = array(
			\CCrmOwnerType::Lead    => 'CCrmLead',
			\CCrmOwnerType::Contact => 'CCrmContact',
			\CCrmOwnerType::Company => 'CCrmCompany',
			\CCrmOwnerType::Deal    => 'CCrmDeal',
			\CCrmOwnerType::Invoice => 'CCrmInvoice',
			\CCrmOwnerType::Quote   => 'CCrmQuote',
		);

		if (\CCrmOwnerType::System == $typeID)
		{
			$entityInfo['FIELDS'] = \CUser::getById($ID)->fetch();
			//$entityInfo['USER_FIELDS'] = $USER_FIELD_MANAGER->getUserFields('USER', $ID, LANGUAGE_ID);
		}
		else if (array_key_exists($typeID, $entityClasses))
		{
			$entityClass = $entityClasses[$typeID];

			$entityInfo['FIELDS'] = $entityClass::getById($ID, false);
			$entityInfo['USER_FIELDS'] = $USER_FIELD_MANAGER->getUserFields($entityClass::$sUFEntityID, $ID, LANGUAGE_ID);
		}
		elseif (\CCrmOwnerType::isUseFactoryBasedApproach($typeID))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($typeID);
			$item = $factory->getItem($ID);
			if ($item)
			{
				$entityInfo['FIELDS'] = $item->getCompatibleData();
				$entityInfo['FIELDS']['HEADING'] = $item->getHeading();
				$entityInfo['USER_FIELDS'] = $USER_FIELD_MANAGER->getUserFields($factory->getUserFieldEntityId(), $ID, LANGUAGE_ID);
			}
		}

		$entityNames = array(
			\CCrmOwnerType::Lead    => \CCrmOwnerType::LeadName,
			\CCrmOwnerType::Contact => \CCrmOwnerType::ContactName,
			\CCrmOwnerType::Company => \CCrmOwnerType::CompanyName,
		);
		if (array_key_exists($typeID, $entityNames))
		{
			$entityInfo['FM'] = array();
			$res = \CCrmFieldMulti::getList(array('ID' => 'ASC'), array('ENTITY_ID' => $entityNames[$typeID], 'ELEMENT_ID' => $ID));
			while ($item = $res->fetch())
			{
				$fieldName = 'FM_'.$item['TYPE_ID'];
				if (empty($entityInfo['FM'][$fieldName]))
					$entityInfo['FM'][$fieldName] = array();
				$entityInfo['FM'][$fieldName][] = $item['VALUE'];
			}
		}

		return $entityInfo;
	}
	private function MapField(&$entityInfo, $fieldName)
	{
		global $USER_FIELD_MANAGER;

		$typeID = isset($entityInfo['TYPE_ID'])
			? (int)$entityInfo['TYPE_ID'] : CCrmOwnerType::Undefined;
		$fields = isset($entityInfo['FIELDS']) && is_array($entityInfo['FIELDS'])
			? $entityInfo['FIELDS'] : array();
		$userFields = isset($entityInfo['USER_FIELDS']) && is_array($entityInfo['USER_FIELDS'])
			? $entityInfo['USER_FIELDS'] : array();

		if(empty($fields))
		{
			return '';
		}

		$isHtml = $this->contentType === CCrmContentType::Html;
		$isBBCode = $this->contentType === CCrmContentType::BBCode;
		$isPlainText = $this->contentType === CCrmContentType::PlainText;

		$entityTypes = array(
			\CCrmOwnerType::Lead,
			\CCrmOwnerType::Deal,
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Invoice,
			\CCrmOwnerType::Quote,
			\CCrmOwnerType::System,
		);

		$factory = null;
		$result = '';
		if (!in_array($typeID, $entityTypes))
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFilterFactory($typeID);
			if (!$factory)
			{
				return $result;
			}
		}

		if($typeID === CCrmOwnerType::Lead)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'NAME':
				case 'SECOND_NAME':
				case 'LAST_NAME':
				case 'TITLE':
				case 'COMPANY_TITLE':
				case 'SOURCE_DESCRIPTION':
				case 'STATUS_DESCRIPTION':
				case 'POST':
				case 'ASSIGNED_BY_WORK_POSITION':
						$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'ADDRESS':
				{
					if($isHtml)
					{
						$result = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
							LeadAddress::mapEntityFields($fields)
						);
					}
					else
					{
						$result = AddressFormatter::getSingleInstance()->formatTextMultiline(
							LeadAddress::mapEntityFields($fields)
						);
					}
					break;
				}
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'SOURCE':
					$result = self::MapReferenceValue(self::PrepareSources(), $fields, 'SOURCE_ID', $isHtml);
					break;
				case 'STATUS':
					$result = self::MapReferenceValue(self::PrepareLeadStatuses(), $fields, 'STATUS_ID', $isHtml);
					break;
				case 'FORMATTED_NAME':
					$result = CCrmLead::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($fields['HONORIFIC']) ? $fields['HONORIFIC'] : '',
							'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
							'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : '',
							'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : ''
						)
					);
					if($isHtml)
					{
						$result = htmlspecialcharsbx($result);
					}
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY_ID']) ? CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID']) : '';
					break;
				case 'OPPORTUNITY':
					$result = isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00;
					break;
				case 'OPPORTUNITY_FORMATTED':
					$result = CCrmCurrency::MoneyToString(
						isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00,
						isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
					);
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Deal)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'TITLE':
				case 'ASSIGNED_BY_WORK_POSITION':
					$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'TYPE':
					$result = self::MapReferenceValue(self::PrepareDealTypes(), $fields, 'TYPE_ID', $isHtml);
					break;
				case 'STAGE':
					$result = self::mapReferenceValue(
						array_column(\CCrmViewHelper::getDealStageInfos($fields['CATEGORY_ID']), 'NAME', 'STATUS_ID'),
						$fields,
						'STAGE_ID',
						$isHtml
					);
					break;
				case 'PROBABILITY':
					$result = (isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0).' %';
					break;
				case 'BEGINDATE':
					$result = isset($fields['BEGINDATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['BEGINDATE'])) : '';
					break;
				case 'CLOSEDATE':
					$result = isset($fields['CLOSEDATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['CLOSEDATE'])) : '';
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY_ID']) ? CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID']) : '';
					break;
				case 'OPPORTUNITY':
					$result = isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00;
					break;
				case 'OPPORTUNITY_FORMATTED':
					$result = CCrmCurrency::MoneyToString(
						isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00,
						isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
					);
					break;
				case 'COMPANY':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Company,
						isset($fields['COMPANY_ID']) ? intval($fields['COMPANY_ID']) : 0
					);
					break;
				case 'CONTACT':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Contact,
						isset($fields['CONTACT_ID']) ? intval($fields['CONTACT_ID']) : 0
					);
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Contact)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'NAME':
				case 'SECOND_NAME':
				case 'LAST_NAME':
				case 'POST':
				case 'SOURCE_DESCRIPTION':
				case 'ASSIGNED_BY_WORK_POSITION':
					$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'ADDRESS':
				{
					if($isHtml)
					{
						$result = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
							ContactAddress::mapEntityFields($fields)
						);
					}
					else
					{
						$result = AddressFormatter::getSingleInstance()->formatTextMultiline(
							ContactAddress::mapEntityFields($fields)
						);
					}
					break;
				}
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'FORMATTED_NAME':
					$result = CCrmContact::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($fields['HONORIFIC']) ? $fields['HONORIFIC'] : '',
							'NAME' => isset($fields['NAME']) ? $fields['NAME'] : '',
							'SECOND_NAME' => isset($fields['SECOND_NAME']) ? $fields['SECOND_NAME'] : '',
							'LAST_NAME' => isset($fields['LAST_NAME']) ? $fields['LAST_NAME'] : ''
						)
					);
					if($isHtml)
					{
						$result = htmlspecialcharsbx($result);
					}
					break;
				case 'SOURCE':
					$result = self::MapReferenceValue(self::PrepareSources(), $fields, 'SOURCE_ID', $isHtml);
					break;
				case 'TYPE':
					$result = self::MapReferenceValue(self::PrepareContactTypes(), $fields, 'TYPE_ID', $isHtml);
					break;
				case 'COMPANY':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Company,
						isset($fields['COMPANY_ID']) ? intval($fields['COMPANY_ID']) : 0
					);
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Company)
		{
			switch($fieldName)
			{
				case 'ID':
					$result = isset($fields[$fieldName]) ? intval($fields[$fieldName]) : 0;
					break;
				case 'TITLE':
				case 'COMPANY_TITLE':
				case 'SOURCE_DESCRIPTION':
				case 'ASSIGNED_BY_WORK_POSITION':
				case 'BANKING_DETAILS':
					$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'ADDRESS':
				case 'ADDRESS_LEGAL':
				{
					$addressTypeId = (
						$fieldName === 'ADDRESS' ? EntityAddressType::Primary : EntityAddressType::Registered
					);
					if($isHtml)
					{
						$result = AddressFormatter::getSingleInstance()->formatHtmlMultiline(
							CompanyAddress::mapEntityFields($fields, ['TYPE_ID' => $addressTypeId])
						);
					}
					else
					{
						$result = AddressFormatter::getSingleInstance()->formatTextMultiline(
							CompanyAddress::mapEntityFields($fields, ['TYPE_ID' => $addressTypeId])
						);
					}
					unset($addressTypeId);
					break;
				}
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'COMPANY_TYPE':
				case 'TYPE':
					$result = self::MapReferenceValue(self::PrepareCompanyTypes(), $fields, 'COMPANY_TYPE', $isHtml);
					break;
				case 'INDUSTRY':
					$result = self::MapReferenceValue(self::PrepareIndustries(), $fields, 'INDUSTRY', $isHtml);
					break;
				case 'EMPLOYEES':
					$result = self::MapReferenceValue(self::PrepareEmployees(), $fields, 'EMPLOYEES', $isHtml);
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY_ID']) ? CCrmCurrency::GetCurrencyName($fields['CURRENCY_ID']) : '';
					break;
				case 'REVENUE':
					$result = isset($fields['REVENUE']) ? $fields['REVENUE'] : 0.00;
					break;
				case 'REVENUE_FORMATTED':
					$result = CCrmCurrency::MoneyToString(
						isset($fields['REVENUE']) ? $fields['REVENUE'] : 0.00,
						isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
					);
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Invoice)
		{
			switch($fieldName)
			{
				case 'ACCOUNT_NUMBER':
				case 'RESPONSIBLE_WORK_POSITION':
					$result = self::MapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'TITLE':
					$result = isset($fields['ORDER_TOPIC']) ? $fields['ORDER_TOPIC'] : '';
					break;
				case 'COMMENTS':
				{
					if($isBBCode)
					{
						$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
					}
					elseif($isPlainText)
					{
						$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
					}
					else
					{
						$result = self::MapFieldValue($fields, $fieldName, false);
					}
					break;
				}
				case 'DATE_BILL':
					$result = isset($fields['DATE_BILL']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_BILL'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_UPDATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_UPDATE'])) : '';
					break;
				case 'RESPONSIBLE_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['RESPONSIBLE_ID']) ? $fields['RESPONSIBLE_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::GetFormattedUserName(isset($fields['CREATED_BY']) ? $fields['CREATED_BY'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY']) ? CCrmCurrency::GetCurrencyName($fields['CURRENCY']) : '';
					break;
				case 'PRICE':
					$result = isset($fields['PRICE']) ? $fields['PRICE'] : 0.00;
					break;
				case 'PRICE_FORMATED':
					$result = CCrmCurrency::MoneyToString(
						isset($fields['PRICE']) ? $fields['PRICE'] : 0.00,
						isset($fields['CURRENCY']) ? $fields['CURRENCY'] : ''
					);
					break;
				case 'COMPANY':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Company,
						isset($fields['UF_COMPANY_ID']) ? intval($fields['UF_COMPANY_ID']) : 0
					);
					break;
				case 'CONTACT':
					$result = self::ResolveEntityInfo(
						CCrmOwnerType::Contact,
						isset($fields['UF_CONTACT_ID']) ? intval($fields['UF_CONTACT_ID']) : 0
					);
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::Quote)
		{
			switch ($fieldName)
			{
				case 'TITLE':
				case 'QUOTE_NUMBER':
				case 'ASSIGNED_BY_WORK_POSITION':
					$result = self::mapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'BEGINDATE':
					$result = isset($fields['BEGINDATE']) ? FormatDate('SHORT', MakeTimeStamp($fields['BEGINDATE'])) : '';
					break;
				case 'STATUS':
					$result = self::mapReferenceValue(self::prepareQuoteStatuses(), $fields, 'STATUS_ID', $isHtml);
					break;
				case 'CURRENCY':
					$result = isset($fields['CURRENCY_ID']) ? CCrmCurrency::getCurrencyName($fields['CURRENCY_ID']) : '';
					break;
				case 'OPPORTUNITY':
					$result = isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00;
					break;
				case 'OPPORTUNITY_FORMATTED':
					$result = CCrmCurrency::moneyToString(
						isset($fields['OPPORTUNITY']) ? $fields['OPPORTUNITY'] : 0.00,
						isset($fields['CURRENCY_ID']) ? $fields['CURRENCY_ID'] : ''
					);
					break;
				case 'ASSIGNED_BY_FULL_NAME':
					$result = CCrmViewHelper::getFormattedUserName(isset($fields['ASSIGNED_BY_ID']) ? $fields['ASSIGNED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'CREATED_BY_FULL_NAME':
					$result = CCrmViewHelper::getFormattedUserName(isset($fields['CREATED_BY_ID']) ? $fields['CREATED_BY_ID'] : 0, '', $isHtml);
					break;
				case 'MODIFY_BY_FULL_NAME':
					$result = CCrmViewHelper::getFormattedUserName(isset($fields['MODIFY_BY_ID']) ? $fields['MODIFY_BY_ID'] : 0, '', $isHtml);
					break;
				case 'DATE_CREATE':
					$result = isset($fields['DATE_CREATE']) ? formatDate('SHORT', makeTimeStamp($fields['DATE_CREATE'])) : '';
					break;
				case 'DATE_MODIFY':
					$result = isset($fields['DATE_MODIFY']) ? formatDate('SHORT', makeTimeStamp($fields['DATE_MODIFY'])) : '';
					break;
				case 'COMPANY':
					$result = self::resolveEntityInfo(
						CCrmOwnerType::Company,
						isset($fields['COMPANY_ID']) ? intval($fields['COMPANY_ID']) : 0
					);
					break;
				case 'CONTACT':
					$result = self::resolveEntityInfo(
						CCrmOwnerType::Contact,
						isset($fields['CONTACT_ID']) ? intval($fields['CONTACT_ID']) : 0
					);
					break;
				case 'LEAD':
					$result = self::resolveEntityInfo(
						CCrmOwnerType::Lead,
						isset($fields['LEAD_ID']) ? intval($fields['LEAD_ID']) : 0
					);
					break;
				case 'DEAL':
					$result = self::resolveEntityInfo(
						CCrmOwnerType::Deal,
						isset($fields['DEAL_ID']) ? intval($fields['DEAL_ID']) : 0
					);
					break;
				case 'CONTENT':
				case 'TERMS':
				case 'COMMENTS':
					if ($isBBCode)
						$result = self::mapHtmlFieldAsBbCode($fields, $fieldName);
					else if ($isPlainText)
						$result = self::mapHtmlFieldAsPlainText($fields, $fieldName);
					else
						$result = self::mapFieldValue($fields, $fieldName, false);
					break;
			}
		}
		elseif($typeID === CCrmOwnerType::System)
		{
			switch ($fieldName)
			{
				case 'NAME':
				case 'SECOND_NAME':
				case 'LAST_NAME':
				case 'EMAIL':
				case 'WORK_POSITION':
				case 'UF_PHONE_INNER':
					$result = self::mapFieldValue($fields, $fieldName, $isHtml);
					break;
				case 'FULL_NAME':
					$result = \CUser::formatName(\CSite::getNameFormat(), $fields, true, $isHtml);
					break;
				case 'WORK_PHONE':
				//case 'PERSONAL_PHONE':
				case 'PERSONAL_MOBILE':
					$result = \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($fields[$fieldName])->format();
					break;
			}
		}
		elseif ($factory)
		{
			if (\Bitrix\Crm\Service\ParentFieldManager::isParentFieldName($fieldName))
			{
				$parentEntityTypeId = \Bitrix\Crm\Service\ParentFieldManager::getEntityTypeIdFromFieldName($fieldName);
				$result = self::ResolveEntityInfo($parentEntityTypeId, $fields[$fieldName]);
			}
			elseif ($fieldName === 'CONTACT')
			{
				$result = self::ResolveEntityInfo(\CCrmOwnerType::Contact, $fields[\Bitrix\Crm\Item::FIELD_NAME_CONTACT_ID]);
			}
			elseif ($fieldName === 'COMPANY')
			{
				$result = self::ResolveEntityInfo(\CCrmOwnerType::Company, $fields[\Bitrix\Crm\Item::FIELD_NAME_COMPANY_ID]);
			}
			elseif ($fieldName === 'MY_COMPANY')
			{
				$result = self::ResolveEntityInfo(\CCrmOwnerType::Company, $fields[\Bitrix\Crm\Item::FIELD_NAME_MYCOMPANY_ID]);
			}
			elseif ($fieldName === 'ASSIGNED_BY_FULL_NAME')
			{
				$result = \Bitrix\Crm\Service\Container::getInstance()->getUserBroker()->getName((int)$fields['ASSIGNED_BY_ID']);
				if ($result && $isHtml)
				{
					$result = htmlspecialcharsEx($result);
				}
			}
			elseif ($fieldName === 'ASSIGNED_BY_WORK_POSITION')
			{
				$result = \Bitrix\Crm\Service\Container::getInstance()->getUserBroker()->getWorkPosition((int)$fields['ASSIGNED_BY_ID']);
				if ($result && $isHtml)
				{
					$result = htmlspecialcharsEx($result);
				}
			}
			elseif ($fieldName === 'TITLE')
			{
				$result = self::mapFieldValue($fields, $fieldName, $isHtml);
				if (empty($result))
				{
					$result = self::mapFieldValue($fields, 'HEADING', $isHtml);
				}
			}
			elseif ($fieldName === 'PRICE')
			{
				$result = $fields[\Bitrix\Crm\Item::FIELD_NAME_OPPORTUNITY] ?? '';
			}
			elseif ($fieldName === \Bitrix\Crm\Item::FIELD_NAME_SOURCE_ID)
			{
				$result = self::MapReferenceValue(self::PrepareSources(), $fields, 'SOURCE_ID', $isHtml);
			}
			elseif ($fieldName === 'CREATED_BY_FULL_NAME')
			{
				$result = CCrmViewHelper::GetFormattedUserName($fields['CREATED_BY'] ?? 0, '', $isHtml);
			}
			elseif ($fieldName === 'PRICE_FORMATED')
			{
				$result = CCrmCurrency::MoneyToString(
					$fields[\Bitrix\Crm\Item::FIELD_NAME_OPPORTUNITY] ?? 0,
					$fields[\Bitrix\Crm\Item::FIELD_NAME_CURRENCY_ID] ?? ''
				);
			}
			elseif ($fieldName === 'COMMENTS')
			{
				if($isBBCode)
				{
					$result = self::MapHtmlFieldAsBbCode($fields, 'COMMENTS');
				}
				elseif($isPlainText)
				{
					$result = self::MapHtmlFieldAsPlainText($fields, 'COMMENTS');
				}
				else
				{
					$result = self::MapFieldValue($fields, $fieldName, false);
				}
			}
			elseif (mb_strpos($fieldName, 'UF_') !== 0)
			{
				$result = self::mapFieldValue($fields, $fieldName, $isHtml);
			}
		}

		if ('' == $result)
		{
			if (is_array($userFields) && array_key_exists($fieldName, $userFields))
			{
				$userTypes = array('string', 'integer', 'double', 'date', 'datetime', 'url', 'enumeration', 'boolean', 'money', 'address');
				if (in_array($userFields[$fieldName]['USER_TYPE_ID'], $userTypes))
				{
					$result = $USER_FIELD_MANAGER->getPublicText($userFields[$fieldName]);

					if ($isHtml)
						$result = htmlspecialcharsbx($result);
				}
			}
			else if (
				isset($entityInfo['FM'])
				&& is_array($entityInfo['FM'])
				&& array_key_exists($fieldName, $entityInfo['FM'])
			)
			{
				$result = (array) $entityInfo['FM'][$fieldName];

				switch ($fieldName)
				{
					case 'FM_EMAIL':
						break;
					case 'FM_PHONE':
						$result = array_map(function ($value)
						{
							return \Bitrix\Main\PhoneNumber\Parser::getInstance()->parse($value)->format();
						}, $entityInfo['FM'][$fieldName]);
						break;
					default:
						$result = array();
				}

				$result = join(', ', $result);
				if ($isHtml)
					$result = htmlspecialcharsbx($result);
			}
		}

		return $result;
	}
	private static function MapFieldValue(&$fields, $fieldID, $htmlEncode = false)
	{
		if(!isset($fields[$fieldID]))
		{
			return '';
		}

		$result = $fields[$fieldID];
		if($htmlEncode)
		{
			$result = htmlspecialcharsEx($result);
		}
		return $result;
	}
	private static function MapHtmlFieldAsBbCode(&$fields, $fieldID)
	{
		if(!isset($fields[$fieldID]))
		{
			return '';
		}

		return \Bitrix\Crm\Format\TextHelper::convertHtmlToBbCode($fields[$fieldID]);
	}
	private static function MapHtmlFieldAsPlainText(&$fields, $fieldID)
	{
		if(!isset($fields[$fieldID]))
		{
			return '';
		}

		$result = $fields[$fieldID];
		$result = preg_replace("/<br(\s*\/\s*)?>/i", PHP_EOL, $result);
		return strip_tags($result);
	}
	private static function MapReferenceValue($items, $fields, $fieldID, $htmlEncode = false)
	{
		$ID = isset($fields[$fieldID]) ? $fields[$fieldID] : '';
		$result = isset($items[$ID]) ? $items[$ID] : $ID;
		if($htmlEncode)
		{
			$result = htmlspecialcharsEx($result);
		}
		return $result;
	}
	private static function PrepareLeadStatuses()
	{
		return self::$LEAD_STATUSES !== null
			? self::$LEAD_STATUSES
			: (self::$LEAD_STATUSES = CCrmStatus::GetStatusListEx('STATUS'));
	}
	private static function prepareQuoteStatuses()
	{
		return self::$QUOTE_STATUSES !== null
			? self::$QUOTE_STATUSES
			: (self::$QUOTE_STATUSES = CCrmStatus::getStatusListEx('QUOTE_STATUS'));
	}
	private static function PrepareSources()
	{
		return self::$SOURCES !== null
			? self::$SOURCES
			: (self::$SOURCES = CCrmStatus::GetStatusListEx('SOURCE'));
	}
	private static function PrepareDealTypes()
	{
		return self::$DEAL_TYPES !== null
			? self::$DEAL_TYPES
			: (self::$DEAL_TYPES = CCrmStatus::GetStatusListEx('DEAL_TYPE'));
	}
	private static function PrepareDealStages()
	{
		return self::$DEAL_STAGES !== null
			? self::$DEAL_STAGES
			: (self::$DEAL_STAGES = CCrmStatus::GetStatusListEx('DEAL_STAGE'));
	}
	private static function PrepareContactTypes()
	{
		return self::$CONTACT_TYPES !== null
			? self::$CONTACT_TYPES
			: (self::$CONTACT_TYPES = CCrmStatus::GetStatusListEx('CONTACT_TYPE'));
	}
	private static function PrepareCompanyTypes()
	{
		return self::$COMPANY_TYPES !== null
			? self::$COMPANY_TYPES
			: (self::$COMPANY_TYPES = CCrmStatus::GetStatusListEx('COMPANY_TYPE'));
	}
	private static function PrepareIndustries()
	{
		return self::$INDUSTRIES !== null
			? self::$INDUSTRIES
			: (self::$INDUSTRIES = CCrmStatus::GetStatusListEx('INDUSTRY'));
	}
	private static function PrepareEmployees()
	{
		return self::$EMPLOYEES !== null
			? self::$EMPLOYEES
			: (self::$EMPLOYEES = CCrmStatus::GetStatusListEx('EMPLOYEES'));
	}
}
