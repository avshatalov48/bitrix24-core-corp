<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\Conversion\EntityConversionMap;
use Bitrix\Crm\Conversion\LeadConversionMapper;

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!function_exists('__CrmLeadConvertConfigPrepareTab'))
{
	function __CrmLeadConvertConfigPrepareTab($entityTypeID, array $entityFieldList, array $listItems, array &$tabFields)
	{
		$map = EntityConversionMap::load(\CCrmOwnerType::Lead, $entityTypeID);
		if($map === null)
		{
			$map = LeadConversionMapper::createMap($entityTypeID);
			$map->save();
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		foreach($entityFieldList as $k => $v)
		{
			$tabFields[] = array(
				'id' => "{$entityTypeName}_{$k}",
				'name' => $v,
				'items' => $listItems,
				'type' => 'list',
				'value' => $map->resolveSourceID($k, '')
			);
		}
	}
}
if(!function_exists('__CrmLeadConvertConfigSaveMap'))
{
	function __CrmLeadConvertConfigSaveMap($entityTypeID, array $entityFieldList, array $data)
	{
		$map = EntityConversionMap::load(\CCrmOwnerType::Lead, $entityTypeID);
		if($map === null)
		{
			$map = LeadConversionMapper::createMap($entityTypeID);
		}

		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeID);
		$entityFieldIDs = array_keys($entityFieldList);
		$entityFieldQty = count($entityFieldIDs);

		for($i = 0; $i < $entityFieldQty; $i++)
		{
			$dstFieldID = $entityFieldIDs[$i];
			$name = "{$entityTypeName}_{$dstFieldID}";

			if(!isset($data[$name]))
			{
				continue;
			}
			$srcFieldID = $data[$name];

			$item = $map->findItemByDestinationID($dstFieldID);
			if($item !== null)
			{
				if($srcFieldID === '')
				{
					$map->removeItem($item);
				}
				elseif($item->getSourceField() != $srcFieldID)
				{
					$item->setSourceField($srcFieldID);
				}
			}
			elseif($srcFieldID !== '')
			{
				$map->createItem($srcFieldID, $dstFieldID, array('IS_LOCKED' => true));
			}
		}

		$map->save();
	}
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$arResult['CAN_EDIT'] = CCrmLead::CheckUpdatePermission(0, $userPermissions);
$arResult['CAN_CONVERT_TO_CONTACT'] = CCrmContact::CheckCreatePermission($userPermissions);
$arResult['CAN_CONVERT_TO_COMPANY'] = CCrmCompany::CheckCreatePermission($userPermissions);
$arResult['CAN_CONVERT_TO_DEAL'] = CCrmDeal::CheckCreatePermission($userPermissions);
$arResult['CAN_CONVERT'] = $arResult['CAN_EDIT']
	&& ($arResult['CAN_CONVERT_TO_CONTACT']
		|| $arResult['CAN_CONVERT_TO_COMPANY']
		|| $arResult['CAN_CONVERT_TO_DEAL']);

if(!$arResult['CAN_CONVERT'])
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['FORM_ID'] = 'CRM_LEAD_CONVERT_CONFIG';
$arResult['FIELDS'] = array();
$arResult['FIELD_LIST'] = array();

global $USER_FIELD_MANAGER;
$addressLabels = EntityAddress::getShortLabels();
$regAddressLabels = EntityAddress::getShortLabels(EntityAddress::Registered);
$multifildInfos = CCrmFieldMulti::GetEntityTypeInfos();

//region Lead Fields
$arResult['FIELD_LIST']['LEAD'] = array(
	'TITLE' => CCrmLead::GetFieldCaption('TITLE'),
	'NAME' => CCrmLead::GetFieldCaption('NAME'),
	'LAST_NAME' => CCrmLead::GetFieldCaption('LAST_NAME'),
	'SECOND_NAME' => CCrmLead::GetFieldCaption('SECOND_NAME'),
	'BIRTHDATE' => CCrmLead::GetFieldCaption('BIRTHDATE'),
	'ADDRESS' => $addressLabels['ADDRESS'],
	'ADDRESS_2' => $addressLabels['ADDRESS_2'],
	'ADDRESS_CITY' => $addressLabels['CITY'],
	'ADDRESS_REGION' => $addressLabels['REGION'],
	'ADDRESS_PROVINCE' => $addressLabels['PROVINCE'],
	'ADDRESS_POSTAL_CODE' => $addressLabels['POSTAL_CODE'],
	'ADDRESS_COUNTRY' => $addressLabels['COUNTRY']
);

foreach($multifildInfos as $k => $v)
{
	$arResult['FIELD_LIST']['LEAD'][$k] = $v['NAME'];
}

$arResult['FIELD_LIST']['LEAD'] = array_merge(
	$arResult['FIELD_LIST']['LEAD'],
	array(
		'COMPANY_TITLE' => CCrmLead::GetFieldCaption('COMPANY_TITLE'),
		'POST' => CCrmLead::GetFieldCaption('POST'),
		'STATUS_ID' => CCrmLead::GetFieldCaption('STATUS_ID'),
		'STATUS_DESCRIPTION' => CCrmLead::GetFieldCaption('STATUS_DESCRIPTION'),
		'OPPORTUNITY' => CCrmLead::GetFieldCaption('OPPORTUNITY'),
		'CURRENCY_ID' => CCrmLead::GetFieldCaption('CURRENCY_ID'),
		'SOURCE_ID' => CCrmLead::GetFieldCaption('SOURCE_ID'),
		'SOURCE_DESCRIPTION' => CCrmLead::GetFieldCaption('SOURCE_DESCRIPTION'),
		'OPENED' => CCrmLead::GetFieldCaption('OPENED'),
		'COMMENTS' => CCrmLead::GetFieldCaption('COMMENTS'),
		'ASSIGNED_BY_ID' => CCrmLead::GetFieldCaption('ASSIGNED_BY_ID'),
		'PRODUCT_ROWS' => CCrmLead::GetFieldCaption('PRODUCT_ROWS')
	)
);
$leadUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);
$arResult['FIELD_LIST']['LEAD'] = array_merge($arResult['FIELD_LIST']['LEAD'], $leadUserType->GetFieldNames());
//endregion
//region Contact Fields
if($arResult['CAN_CONVERT_TO_CONTACT'])
{

	$arResult['FIELD_LIST']['CONTACT'] = array(
		'NAME' => CCrmContact::GetFieldCaption('NAME'),
		'LAST_NAME' => CCrmContact::GetFieldCaption('LAST_NAME'),
		'SECOND_NAME' => CCrmContact::GetFieldCaption('SECOND_NAME'),
		'BIRTHDATE' => CCrmContact::GetFieldCaption('BIRTHDATE'),
		'PHOTO' => CCrmContact::GetFieldCaption('PHOTO'),
		'ADDRESS' => $addressLabels['ADDRESS'],
		'ADDRESS_2' => $addressLabels['ADDRESS_2'],
		'ADDRESS_CITY' => $addressLabels['CITY'],
		'ADDRESS_REGION' => $addressLabels['REGION'],
		'ADDRESS_PROVINCE' => $addressLabels['PROVINCE'],
		'ADDRESS_POSTAL_CODE' => $addressLabels['POSTAL_CODE'],
		'ADDRESS_COUNTRY' => $addressLabels['COUNTRY']
	);

	foreach($multifildInfos as $k => $v)
	{
		$arResult['FIELD_LIST']['CONTACT'][$k] = $v['NAME'];
	}

	$arResult['FIELD_LIST']['CONTACT'] = array_merge(
		$arResult['FIELD_LIST']['CONTACT'],
		array(
			'POST' => CCrmContact::GetFieldCaption('POST'),
			'TYPE_ID' => CCrmContact::GetFieldCaption('TYPE_ID'),
			'SOURCE_ID' => CCrmContact::GetFieldCaption('SOURCE_ID'),
			'SOURCE_DESCRIPTION' => CCrmContact::GetFieldCaption('SOURCE_DESCRIPTION'),
			'EXPORT' => CCrmContact::GetFieldCaption('EXPORT'),
			'OPENED' => CCrmContact::GetFieldCaption('OPENED'),
			'COMMENTS' => CCrmContact::GetFieldCaption('COMMENTS'),
			'ASSIGNED_BY_ID' => CCrmContact::GetFieldCaption('ASSIGNED_BY_ID')
		)
	);
	$contactUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);
	$arResult['FIELD_LIST']['CONTACT'] = array_merge($arResult['FIELD_LIST']['CONTACT'], $contactUserType->GetFieldNames());
}
//endregion
//region Company Fields
if($arResult['CAN_CONVERT_TO_COMPANY'])
{
	$arResult['FIELD_LIST']['COMPANY'] = array(
		'TITLE' => CCrmCompany::GetFieldCaption('TITLE'),
		'LOGO' => CCrmCompany::GetFieldCaption('LOGO'),
		'COMPANY_TYPE' => CCrmCompany::GetFieldCaption('COMPANY_TYPE'),
		'INDUSTRY' => CCrmCompany::GetFieldCaption('INDUSTRY'),
		'EMPLOYEES' => CCrmCompany::GetFieldCaption('EMPLOYEES'),
		'REVENUE' => CCrmCompany::GetFieldCaption('REVENUE'),
		'CURRENCY_ID' => CCrmCompany::GetFieldCaption('CURRENCY_ID'),
		'ADDRESS' => $addressLabels['ADDRESS'],
		'ADDRESS_2' => $addressLabels['ADDRESS_2'],
		'ADDRESS_CITY' => $addressLabels['CITY'],
		'ADDRESS_REGION' => $addressLabels['REGION'],
		'ADDRESS_PROVINCE' => $addressLabels['PROVINCE'],
		'ADDRESS_POSTAL_CODE' => $addressLabels['POSTAL_CODE'],
		'ADDRESS_COUNTRY' => $addressLabels['COUNTRY'],
		'REG_ADDRESS' => $regAddressLabels['ADDRESS'],
		'REG_ADDRESS_2' => $regAddressLabels['ADDRESS_2'],
		'REG_ADDRESS_CITY' => $regAddressLabels['CITY'],
		'REG_ADDRESS_REGION' => $regAddressLabels['REGION'],
		'REG_ADDRESS_PROVINCE' => $regAddressLabels['PROVINCE'],
		'REG_ADDRESS_POSTAL_CODE' => $regAddressLabels['POSTAL_CODE'],
		'REG_ADDRESS_COUNTRY' => $regAddressLabels['COUNTRY']
	);

	foreach($multifildInfos as $k => $v)
	{
		$arResult['FIELD_LIST']['COMPANY'][$k] = $v['NAME'];
	}

	$arResult['FIELD_LIST']['COMPANY'] = array_merge(
		$arResult['FIELD_LIST']['COMPANY'],
		array(
			'BANKING_DETAILS' => CCrmCompany::GetFieldCaption('BANKING_DETAILS'),
			'OPENED' => CCrmCompany::GetFieldCaption('OPENED'),
			'COMMENTS' => CCrmCompany::GetFieldCaption('COMMENTS'),
			'ASSIGNED_BY_ID' => CCrmCompany::GetFieldCaption('ASSIGNED_BY_ID')
		)
	);
	$companyUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
	$arResult['FIELD_LIST']['COMPANY'] = array_merge($arResult['FIELD_LIST']['COMPANY'], $companyUserType->GetFieldNames());
}
//endregion
//region Deal Fields
if($arResult['CAN_CONVERT_TO_DEAL'])
{
	$arResult['FIELD_LIST']['DEAL'] = array(
		'TITLE' => CCrmDeal::GetFieldCaption('TITLE'),
		'PROBABILITY' => CCrmDeal::GetFieldCaption('PROBABILITY'),
		'COMPANY_ID' => CCrmDeal::GetFieldCaption('COMPANY_ID'),
		'CONTACT_ID' => CCrmDeal::GetFieldCaption('CONTACT_ID'),
		'OPPORTUNITY' => CCrmDeal::GetFieldCaption('OPPORTUNITY'),
		'CURRENCY_ID' => CCrmDeal::GetFieldCaption('CURRENCY_ID'),
		'STAGE_ID' => CCrmDeal::GetFieldCaption('STAGE_ID'),
		'TYPE_ID' => CCrmDeal::GetFieldCaption('TYPE_ID'),
		'BEGINDATE' => CCrmDeal::GetFieldCaption('BEGINDATE'),
		'CLOSEDATE' => CCrmDeal::GetFieldCaption('CLOSEDATE')
	);

	$arResult['FIELD_LIST']['DEAL'] = array_merge(
		$arResult['FIELD_LIST']['DEAL'],
		array(
			'OPENED' => CCrmDeal::GetFieldCaption('OPENED'),
			'COMMENTS' => CCrmDeal::GetFieldCaption('COMMENTS'),
			'ASSIGNED_BY_ID' => CCrmDeal::GetFieldCaption('ASSIGNED_BY_ID'),
			'PRODUCT_ROWS' => CCrmDeal::GetFieldCaption('PRODUCT_ROWS')
		)
	);
	$dealUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);
	$arResult['FIELD_LIST']['DEAL'] = array_merge($arResult['FIELD_LIST']['DEAL'], $dealUserType->GetFieldNames());
}
//endregion

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	if(isset($_POST['save']) || isset($_POST['apply']))
	{
		if($arResult['CAN_CONVERT_TO_CONTACT'])
		{
			__CrmLeadConvertConfigSaveMap(CCrmOwnerType::Contact, $arResult['FIELD_LIST']['CONTACT'], $_POST);
		}

		if($arResult['CAN_CONVERT_TO_COMPANY'])
		{
			__CrmLeadConvertConfigSaveMap(CCrmOwnerType::Company, $arResult['FIELD_LIST']['COMPANY'], $_POST);
		}

		if($arResult['CAN_CONVERT_TO_DEAL'])
		{
			__CrmLeadConvertConfigSaveMap(CCrmOwnerType::Deal, $arResult['FIELD_LIST']['DEAL'], $_POST);
		}
	}
}

$arResult['FIELDS']['tab_contact'] = array();
__CrmLeadConvertConfigPrepareTab(
	CCrmOwnerType::Contact,
	$arResult['FIELD_LIST']['CONTACT'],
	array_merge(array('' => GetMessage('CRM_LEAD_CONVERT_CFG_NOT_SELECTED')), $arResult['FIELD_LIST']['LEAD']),
	$arResult['FIELDS']['tab_contact']
);

$arResult['FIELDS']['tab_company'] = array();
__CrmLeadConvertConfigPrepareTab(
	CCrmOwnerType::Company,
	$arResult['FIELD_LIST']['COMPANY'],
	array_merge(array('' => GetMessage('CRM_LEAD_CONVERT_CFG_NOT_SELECTED')), $arResult['FIELD_LIST']['LEAD']),
	$arResult['FIELDS']['tab_company']
);

$arResult['FIELDS']['tab_deal'] = array();
__CrmLeadConvertConfigPrepareTab(
	CCrmOwnerType::Deal,
	$arResult['FIELD_LIST']['DEAL'],
	array_merge(array('' => GetMessage('CRM_LEAD_CONVERT_CFG_NOT_SELECTED')), $arResult['FIELD_LIST']['LEAD']),
	$arResult['FIELDS']['tab_deal']
);

$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$this->IncludeComponentTemplate();
?>