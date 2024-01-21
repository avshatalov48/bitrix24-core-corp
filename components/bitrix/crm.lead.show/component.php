<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

use Bitrix\Crm\Format\AddressFormatter;
use Bitrix\Crm\Conversion\LeadConversionDispatcher;
use Bitrix\Crm\LeadAddress;
use Bitrix\Crm\Restriction\RestrictionManager;

CUtil::InitJSCore(array('ajax', 'tooltip'));
$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if ($arResult['ELEMENT_ID'] <= 0 || !CCrmLead::CheckReadPermission($arResult['ELEMENT_ID'], $userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_EDIT'] = CCrmLead::CheckUpdatePermission($arResult['ELEMENT_ID'], $userPermissions);
CCrmLead::PrepareConversionPermissionFlags($arResult['ELEMENT_ID'], $arResult, $userPermissions);

$arResult['EDITABLE_FIELDS'] = array();

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arResult['PATH_TO_LEAD_SHOW'] = $arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

global $USER_FIELD_MANAGER;
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);

$obFields = CCrmLead::GetListEx(
	array(),
	array('ID' => $arParams['ELEMENT_ID'])
);
$arFields = $obFields->GetNext();

$dbResMultiFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
);
$arFields['FM'] = array();
while($arMultiFields = $dbResMultiFields->Fetch())
{
	$arFields['FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
}

$arResult['isReturnCustomer'] = $arFields['IS_RETURN_CUSTOMER'] == 'Y';

if ($arResult['isReturnCustomer'])
{
	$arResult['CAN_CONVERT_TO_CONTACT'] = false;
	$arResult['CAN_CONVERT_TO_COMPANY'] = false;
	$arResult['TACTILE_FORM_ID'] = 'CRM_RETURN_CUSTOMER_LEAD_EDIT_V12';
}
else
{
	$arResult['TACTILE_FORM_ID'] = 'CRM_LEAD_EDIT_V12';
}

$fullNameFormat = $arParams['NAME_TEMPLATE'];
$arFields['~ASSIGNED_BY_FORMATTED_NAME'] = intval($arFields['~ASSIGNED_BY_ID']) > 0
	? CUser::FormatName(
		$fullNameFormat,
		array(
			'LOGIN' => $arFields['~ASSIGNED_BY_LOGIN'],
			'NAME' => $arFields['~ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arFields['~ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arFields['~ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	) : GetMessage('RESPONSIBLE_NOT_ASSIGNED');

$arFields['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~ASSIGNED_BY_FORMATTED_NAME']);
$arFields['~CREATED_BY_FORMATTED_NAME'] = CUser::FormatName($fullNameFormat,
	array(
		'LOGIN' => $arFields['~CREATED_BY_LOGIN'],
		'NAME' => $arFields['~CREATED_BY_NAME'],
		'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'],
		'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME']
	),
	true, false
);

$arFields['CREATED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~CREATED_BY_FORMATTED_NAME']);

$arFields['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
	array(
		'user_id' => $arFields['ASSIGNED_BY']
	)
);

$arFields['~MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName($fullNameFormat,
	array(
	'LOGIN' => $arFields['~MODIFY_BY_LOGIN'],
	'NAME' => $arFields['~MODIFY_BY_NAME'],
	'LAST_NAME' => $arFields['~MODIFY_BY_LAST_NAME'],
	'SECOND_NAME' => $arFields['~MODIFY_BY_SECOND_NAME']
	),
	true, false
);

$arFields['MODIFY_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~MODIFY_BY_FORMATTED_NAME']);

$arFields['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
	array(
		'user_id' => $arFields['MODIFY_BY']
	)
);

$honorific = isset($arFields['~HONORIFIC']) ? $arFields['~HONORIFIC'] : '';
$name = isset($arFields['~NAME']) ? $arFields['~NAME'] : '';
$secondName = isset($arFields['~SECOND_NAME']) ? $arFields['~SECOND_NAME'] : '';
$lastName = isset($arFields['~LAST_NAME']) ? $arFields['~LAST_NAME'] : '';

$arFields['~FORMATTED_NAME'] = ($name !== '' || $secondName !== '' || $lastName !== '')
	? CCrmLead::PrepareFormattedName(
		array(
			'HONORIFIC' => $honorific,
			'NAME' => $name,
			'SECOND_NAME' => $secondName,
			'LAST_NAME' => $lastName
		)
	) : '';

$arFields['FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~FORMATTED_NAME']);

$arFields['PATH_TO_LEAD_CONVERT'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_LEAD_CONVERT'],
	array('lead_id' => $arFields['~ID'])
);


$arResult['CONVERSION_TYPE_ID'] = LeadConversionDispatcher::resolveTypeID($arFields);
if($arResult['CAN_CONVERT'])
{
	$conversionConfig = LeadConversionDispatcher::getConfiguration(array('FIELDS' => $arFields));
	$schemeID = $conversionConfig->getCurrentSchemeID();

	$arResult['CONVERSION_SCHEME'] = array(
		'ORIGIN_URL' => $APPLICATION->GetCurPage(),
		'SCHEME_ID' => $schemeID,
		'SCHEME_NAME' => \Bitrix\Crm\Conversion\LeadConversionScheme::resolveName($schemeID),
		'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\LeadConversionScheme::getDescription($schemeID),
		'SCHEME_CAPTION' => GetMessage('CRM_LEAD_CREATE_ON_BASIS')
	);

	$arResult['CONVERSION_CONFIGS'] = LeadConversionDispatcher::getJavaScriptConfigurations();
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$isExternal = $arResult['IS_EXTERNAL'] = isset($arResult['ELEMENT']['ORIGINATOR_ID']) && isset($arResult['ELEMENT']['ORIGIN_ID']) && intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0;
// Instant edit disallowed for leads in 'CONVERTED' status
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];

if (empty($arResult['ELEMENT']['ID']))
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));

if ($arResult['isReturnCustomer'])
{
	$arResult['FORM_ID'] = 'CRM_RETURN_CUSTOMER_LEAD_SHOW_V12';
}
else
{
	$arResult['FORM_ID'] = 'CRM_LEAD_SHOW_V12';
}
$arResult['GRID_ID'] = 'CRM_LEAD_LIST_V12';
$arResult['PRODUCT_ROW_TAB_ID'] = 'tab_product_rows';
$arResult['BACK_URL'] = $arParams['PATH_TO_LEAD_LIST'];
$arResult['ALL_STATUS_LIST'] = $arResult['STATUS_LIST'] = CCrmStatus::GetStatusList('STATUS');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

$arResult['ELEMENT']['~STATUS_TEXT'] = $arResult['STATUS_LIST'][$arResult['ELEMENT']['~STATUS_ID']];
$arResult['ELEMENT']['STATUS_TEXT'] = htmlspecialcharsbx($arResult['ELEMENT']['~STATUS_TEXT']);

$arResult['FIELDS'] = array();

$readOnlyMode = !$enableInstantEdit || $isExternal;

// LEAD SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_lead_info',
	'name' => GetMessage('CRM_SECTION_LEAD'),
	'type' => 'section',
	'isTactile' => true
);

// TITLE -->
// TITLE is displayed in header. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TITLE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'label',
	'isTactile' => true
);
// <-- TITLE

// STATUS_ID -->
// STATUS_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'STATUS_ID';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_FIELD_STATUS_ID_MSGVER_1'),
	'type' => 'label',
	'value' => $arResult['STATUS_LIST'][$arResult['ELEMENT']['~STATUS_ID']],
	'isTactile' => true
);

// Prevent selection of 'CONVERTED' status in GUI
unset($arResult['STATUS_LIST']['CONVERTED']);
//<-- STATUS_ID

// STATUS_DESCRIPTION -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'STATUS_ID';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_STATUS_DESCRIPTION_MSGVER_1'),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['~STATUS_DESCRIPTION']) ? $arResult['ELEMENT']['~STATUS_DESCRIPTION'] : '',
	'isTactile' => true
);

// CURRENCY -->
// CURRENCY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['CURRENCY_LIST'][$currencyID]) ? htmlspecialcharsbx($arResult['CURRENCY_LIST'][$currencyID]) : $currencyID,
	'isTactile' => true
);
// <-- CURRENCY

// OPPORTUNITY -->
// OPPORTUNITY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPPORTUNITY';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? CCrmCurrency::MoneyToString($arResult['ELEMENT']['OPPORTUNITY'], $currencyID, '#') : '',
	'isTactile' => true
);
// <-- OPPORTUNITY

// SOURCE_ID -->
// SOURCE_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'SOURCE_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'label',
	'items' => $arResult['SOURCE_LIST'],
	'value' => $arResult['SOURCE_LIST'][$arResult['ELEMENT']['~SOURCE_ID']],
	'isTactile' => true
);
// <-- SOURCE_ID

// SOURCE_DESCRIPTION -->
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'SOURCE_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'label',
	'items' => $arResult['SOURCE_LIST'],
	'value' => isset($arResult['ELEMENT']['~SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['~SOURCE_DESCRIPTION'] : '',
	'isTactile' => true
);
// <-- SOURCE_DESCRIPTION

// ASSIGNED_BY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'ASSIGNED_BY_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'custom',
	'value' => CCrmViewHelper::PrepareFormResponsible($arResult['ELEMENT']['~ASSIGNED_BY_ID'], $arParams['NAME_TEMPLATE'], $arParams['PATH_TO_USER_PROFILE']),
	'isTactile' => true
);
// <-- ASSIGNED_BY_ID

// OPENED -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPENED';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'label',
	'params' => array(),
	'value' =>  (isset($arResult['ELEMENT']['~OPENED']) && $arResult['ELEMENT']['~OPENED'] == 'Y') ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
	'isTactile' => true
);
// <-- OPENED
//<-- LEAD SECTION

// CONTACT INFO SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO_2'),
	'type' => 'section',
	'isTactile' => true
);

if ($arResult['ELEMENT']['IS_RETURN_CUSTOMER'] != 'Y')
{
	// HONORIFIC -->
	$honorificList = CCrmStatus::GetStatusList('HONORIFIC');
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'HONORIFIC',
		'name' => GetMessage('CRM_FIELD_HONORIFIC'),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['~HONORIFIC']) && isset($honorificList[$arResult['ELEMENT']['~HONORIFIC']])
			? $honorificList[$arResult['ELEMENT']['~HONORIFIC']] : '',
		'isTactile' => true
	);
	//<-- HONORIFIC


	// LAST_NAME -->
	if($enableInstantEdit)
	{
		$arResult['EDITABLE_FIELDS'][] = 'LAST_NAME';
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'LAST_NAME',
		'name' => GetMessage('CRM_FIELD_LAST_NAME'),
		'type' => 'label',
		'params' => array(),
		'value' =>  isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
		'isTactile' => true
	);
	// <-- LAST_NAME

	// NAME -->
	if($enableInstantEdit)
	{
		$arResult['EDITABLE_FIELDS'][] = 'NAME';
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'NAME',
		'name' => GetMessage('CRM_FIELD_NAME'),
		'type' => 'label',
		'params' => array(),
		'value' =>  isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
		'isTactile' => true
	);
	// <-- NAME

	// SECOND_NAME -->
	if($enableInstantEdit)
	{
		$arResult['EDITABLE_FIELDS'][] = 'SECOND_NAME';
	}
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'SECOND_NAME',
		'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
		'type' => 'label',
		'params' => array(),
		'value' =>  isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : '',
		'isTactile' => true
	);
	// <-- SECOND_NAME

	// BIRTHDATE -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'BIRTHDATE',
		'name' => GetMessage('CRM_LEAD_SHOW_FIELD_BIRTHDATE'),
		'type' => 'label',
		'params' => array('size' => 20),
		'value' => !empty($arResult['ELEMENT']['~BIRTHDATE']) ? ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['~BIRTHDATE']), 'SHORT', SITE_ID) : '',
		'isTactile' => true
	);
	//<-- BIRTHDATE

	$arMutliFieldTypeInfos = CCrmFieldMulti::GetEntityTypes();
	$prefix = mb_strtolower($arResult['FORM_ID']);
	// EMAIL -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'EMAIL',
		'name' => GetMessage('CRM_FIELD_EMAIL'),
		'type' => 'custom',
		'colspan' => true,
		'value' => CCrmViewHelper::PrepareFormMultiField($arResult['ELEMENT'], 'EMAIL', $prefix, $arMutliFieldTypeInfos),
		'isTactile' => true
	);
	//<-- EMAIL

	// PHONE -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'PHONE',
		'name' => GetMessage('CRM_FIELD_PHONE'),
		'type' => 'custom',
		'colspan' => true,
		'value' => CCrmViewHelper::PrepareFormMultiField(
			$arResult['ELEMENT'],
			'PHONE',
			$prefix,
			$arMutliFieldTypeInfos,
			array(
				'ENABLE_SIP' => true,
				'SIP_PARAMS' => array(
					'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::LeadName,
					'ENTITY_ID' => $arResult['ELEMENT_ID']
				)
			)
		),
		'isTactile' => true
	);
	//<-- PHONE

	// WEB -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'WEB',
		'name' => GetMessage('CRM_FIELD_WEB'),
		'type' => 'custom',
		'colspan' => true,
		'value' => CCrmViewHelper::PrepareFormMultiField($arResult['ELEMENT'], 'WEB', $prefix, $arMutliFieldTypeInfos),
		'isTactile' => true
	);
	// <-- WEB

	// IM -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'IM',
		'name' => GetMessage('CRM_FIELD_MESSENGER'),
		'type' => 'custom',
		'colspan' => true,
		'value' => CCrmViewHelper::PrepareFormMultiField($arResult['ELEMENT'], 'IM', $prefix, $arMutliFieldTypeInfos),
		'isTactile' => true
	);
	// <-- IM

	// COMPANY TITLE -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'COMPANY_TITLE',
		'name' => GetMessage('CRM_FIELD_COMPANY_TITLE_2'),
		'type' => 'label',
		'params' => array('size' => 50),
		'value' => isset($arResult['ELEMENT']['~COMPANY_TITLE']) ? $arResult['ELEMENT']['~COMPANY_TITLE'] : '',
		'isTactile' => true
	);
	// <-- COMPANY TITLE

	// POST -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'POST',
		'name' => GetMessage('CRM_FIELD_POST'),
		'type' => 'label',
		'params' => array('size' => 50),
		'value' => isset($arResult['ELEMENT']['~POST']) ? $arResult['ELEMENT']['~POST'] : '',
		'isTactile' => true
	);
	//<-- POST

	// ADDRESS -->
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDRESS',
		'name' => GetMessage('CRM_FIELD_ADDRESS'),
		'type' => 'custom',
		'value' => AddressFormatter::getSingleInstance()->formatHtmlMultiline(
			LeadAddress::mapEntityFields($arResult['ELEMENT'])
		),
		'isTactile' => true
	);
	//<-- ADDRESS
}
else
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CONTACT_ID',
		'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
		'type' => 'crm_single_client_selector',
		'componentParams' => array(
			'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "LEAD_{$arParams['ELEMENT_ID']}" : 'NEWLEAD',
			'ENTITY_TYPE' => CCrmOwnerType::ContactName,
			'ENTITY_ID' => !empty($arResult['ELEMENT']['CONTACT_ID'])? $arResult['ELEMENT']['CONTACT_ID'] : 0,
			'ENTITY_INPUT_NAME' => 'CONTACT_ID',
			'ENABLE_REQUISITES'=> false,
			'ENABLE_ENTITY_CREATION'=> CCrmContact::CheckCreatePermission($userPermissions),
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			'READ_ONLY' => true
		),
		'isTactile' => true
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'COMPANY_ID',
		'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
		'type' => 'crm_single_client_selector',
		'componentParams' => array(
			'CONTEXT' => $arParams['ELEMENT_ID'] > 0 ? "LEAD_{$arParams['ELEMENT_ID']}" : 'NEWLEAD',
			'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
			'ENTITY_ID' => !empty($arResult['ELEMENT']['COMPANY_ID'])? $arResult['ELEMENT']['COMPANY_ID'] : 0,
			'ENTITY_INPUT_NAME' => 'COMPANY_ID',
			'ENABLE_REQUISITES'=> false,
			'ENABLE_ENTITY_CREATION'=> CCrmCompany::CheckCreatePermission($userPermissions),
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			'READ_ONLY' => true
		),
		'isTactile' => true
	);
}


// COMMENTS -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'isTactile' => true
);
// <-- COMMENTS

//<-- CONTACT INFO SECTION

if ($arResult['ELEMENT']['STATUS_ID'] == 'CONVERTED')
{
	if (!$userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
	{
		$internalFilter = array();
		$contactID = isset($arResult['ELEMENT']['CONTACT_ID']) ? $arResult['ELEMENT']['CONTACT_ID'] : 0;
		if($contactID > 0)
		{
			$internalFilter['ID'] = $contactID;
		}
		else
		{
			//Stub
			$internalFilter['LEAD_ID'] = $arParams['ELEMENT_ID'];
		}

		$arResult['FIELDS']['tab_contact'][] = array(
			'id' => 'LEAD_CONTACTS',
			'name' => GetMessage('CRM_FIELD_LEAD_CONTACTS'),
			'colspan' => true,
			'type' => 'crm_contact_list',
			'componentData' => array(
				'template' => '',
				'enableLazyLoad' => true,
				'params' => array(
					'CONTACT_COUNT' => '20',
					'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
					'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
					'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
					'INTERNAL_FILTER' => $internalFilter,
					'GRID_ID_SUFFIX' => 'LEAD_SHOW',
					'FORM_ID' => $arResult['FORM_ID'],
					'TAB_ID' => 'tab_contact',
					'PRESERVE_HISTORY' => true
				)
			)
		);
	}
	if (!$userPermissions->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
	{
		$internalFilter = array();
		$companyID = isset($arResult['ELEMENT']['COMPANY_ID']) ? $arResult['ELEMENT']['COMPANY_ID'] : 0;
		if($companyID > 0)
		{
			$internalFilter['ID'] = $companyID;
		}
		else
		{
			//Stub
			$internalFilter['LEAD_ID'] = $arParams['ELEMENT_ID'];
		}

		$arResult['FIELDS']['tab_company'][] = array(
			'id' => 'DEAL_COMPANY',
			'name' => GetMessage('CRM_FIELD_LEAD_COMPANY'),
			'colspan' => true,
			'type' => 'crm_company_list',
			'componentData' => array(
				'template' => '',
				'enableLazyLoad' => true,
				'params' => array(
					'COMPANY_COUNT' => '20',
					'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
					'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
					'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
					'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
					'INTERNAL_FILTER' => $internalFilter,
					'GRID_ID_SUFFIX' => 'LEAD_SHOW',
					'FORM_ID' => $arResult['FORM_ID'],
					'TAB_ID' => 'tab_company',
					'PRESERVE_HISTORY' => true,
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
				)
			)
		);


	}
	if (CCrmDeal::CheckReadPermission(0, $userPermissions))
	{
		$arResult['FIELDS']['tab_deal'][] = array(
			'id' => 'LEAD_DEAL',
			'name' => GetMessage('CRM_FIELD_LEAD_DEAL'),
			'colspan' => true,
			'type' => 'crm_deal_list',
			'componentData' => array(
				'template' => '',
				'enableLazyLoad' => true,
				'params' => array(
					'DEAL_COUNT' => '20',
					'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'],
					'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
					'INTERNAL_FILTER' => array('LEAD_ID' => $arParams['ELEMENT_ID']),
					'GRID_ID_SUFFIX' => 'LEAD_SHOW',
					'FORM_ID' => $arResult['FORM_ID'],
					'TAB_ID' => 'tab_deal',
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
					'PRESERVE_HISTORY' => true,
				)
			)
		);
	}
}

if (!$userPermissions->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	$arResult['FIELDS']['tab_quote'][] = array(
		'id' => 'LEAD_QUOTE',
		'name' => GetMessage('CRM_FIELD_LEAD_QUOTE'),
		'colspan' => true,
		'type' => 'crm_quote_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'QUOTE_COUNT' => '20',
				'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'],
				'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'],
				'INTERNAL_FILTER' => array('LEAD_ID' => $arResult['ELEMENT']['ID']),
				'INTERNAL_CONTEXT' => array('LEAD_ID' => $arResult['ELEMENT']['ID']),
				'GRID_ID_SUFFIX' => 'LEAD_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_quote',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => true,
				'PRESERVE_HISTORY' => true,
			)
		)
	);
}

// ADDITIONAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section',
	'isTactile' => true
);

// UTM -->
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.utm.entity.view', '',
	array(
		'FIELDS' => $arResult['ELEMENT']
	),
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'UTM',
	'name' => GetMessage('CRM_FIELD_UTM'),
	'params' => array(),
	'type' => 'custom',
	'value' => $sVal,
	'isTactile' => true
);
// <-- UTM

// CREATED_BY_ID -->
// CREATED_BY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
ob_start();
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'ID' => $arResult['ELEMENT']['CREATED_BY'],
		'HTML_ID' => 'crm_created_by',
		'USE_THUMBNAIL_LIST' => 'Y',
		'SHOW_YEAR' => 'M',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '3600',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'SHOW_LOGIN' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sVal = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CREATED_BY_ID',
	'name' => GetMessage('CRM_FIELD_CREATED_BY_ID'),
	'type' => 'custom',
	'value' => $sVal
);
// <-- CREATED_BY_ID

// DATE_CREATE -->
// DATE_CREATE is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_CREATE',
	'name' => GetMessage('CRM_FIELD_DATE_CREATE'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['DATE_CREATE']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_CREATE']), (time() + CTimeZone::GetOffset())) : ''
);
// <-- DATE_CREATE

// MODIFY_BY_ID, DATE_MODIFY -->
// MODIFY_BY_ID, DATE_MODIFY are displayed in sidebar. The field is added for COMPATIBILITY ONLY
if ($arResult['ELEMENT']['DATE_CREATE'] != $arResult['ELEMENT']['DATE_MODIFY'])
{
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'ID' => $arResult['ELEMENT']['MODIFY_BY'],
			'HTML_ID' => 'crm_modify_by',
			'USE_THUMBNAIL_LIST' => 'Y',
			'SHOW_YEAR' => 'M',
			'CACHE_TYPE' => 'A',
			'CACHE_TIME' => '3600',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'SHOW_LOGIN' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'MODIFY_BY_ID',
		'name' => GetMessage('CRM_FIELD_MODIFY_BY_ID'),
		'type' => 'custom',
		'value' => $sVal
	);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_MODIFY',
		'name' => GetMessage('CRM_FIELD_DATE_MODIFY'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['DATE_MODIFY']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_MODIFY']), (time() + CTimeZone::GetOffset())) : ''
	);
}

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	false,
	true,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		'IS_TACTILE' => true
	)
);
// <-- ADDITIONAL SECTION


// LIVE FEED SECTION -->
if (\Bitrix\Crm\Integration\Socialnetwork\Livefeed\AvailabilityHelper::isAvailable())
{
	$arResult['FIELDS']['tab_live_feed'][] = [
		'id' => 'section_live_feed',
		'name' => GetMessage('CRM_SECTION_LIVE_FEED'),
		'type' => 'section'
	];

	$liveFeedHtml = '';
	if ($arParams['ELEMENT_ID'] > 0)
	{
		if (CCrmLiveFeedComponent::needToProcessRequest($_SERVER['REQUEST_METHOD'], $_REQUEST))
		{
			ob_start();
			$APPLICATION->IncludeComponent('bitrix:crm.entity.livefeed',
				'',
				[
					'DATE_TIME_FORMAT' => (LANGUAGE_ID == 'en' ? "j F Y g:i a" : (LANGUAGE_ID == 'de' ? "j. F Y, G:i" : "j F Y G:i")),
					'CAN_EDIT' => $arResult['CAN_EDIT'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Lead,
					'ENTITY_ID' => $arParams['ELEMENT_ID'],
					'FORM_ID' => $arResult['FORM_ID'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE']
				],
				null,
				['HIDE_ICONS' => 'Y']
			);
			$liveFeedHtml = ob_get_contents();
			ob_end_clean();
			$arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] = false;
		}
		else
		{
			$liveFeedContainerID = $arResult['LIVE_FEED_CONTAINER_ID'] = $arResult['FORM_ID'] . '_live_feed_wrapper';
			$liveFeedHtml = '<div id="' . htmlspecialcharsbx($liveFeedContainerID) . '"></div>';
			$arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] = true;
		}
	}

	$arResult['FIELDS']['tab_live_feed'][] = [
		'id' => 'LIVE_FEED',
		'name' => GetMessage('CRM_FIELD_LIVE_FEED'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $liveFeedHtml
	];
}
// <-- LIVE FEED SECTION

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'section_activity_grid',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_MAIN'),
	'type' => 'section'
);

global $DB;
$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'LEAD_ACTIVITY_GRID',
	'name' => GetMessage('CRM_FIELD_LEAD_ACTIVITY'),
	'colspan' => true,
	'type' => 'crm_activity_list',
	'componentData' => array(
		'template' => 'grid',
		'enableLazyLoad' => true,
		'params' => array(
			'BINDINGS' => array(array('TYPE_NAME' => 'LEAD', 'ID' => $arParams['ELEMENT_ID'])),
			'PREFIX' => 'LEAD_ACTIONS_GRID',
			'PERMISSION_TYPE' => 'WRITE',
			'FORM_TYPE' => 'show',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_activity',
			'USE_QUICK_FILTER' => 'Y',
			'PRESERVE_HISTORY' => true
		)
	)
);

// PRODUCT ROW SECTION -->
$arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);

$sProductsHtml = '';
$arResult['PRODUCT_ROW_EDITOR_ID'] = 'lead_'.strval($arParams['ELEMENT_ID']).'_product_editor';
if($arParams['ELEMENT_ID'] > 0)
{
	$bTaxMode = CCrmTax::isTaxMode();

	// Determine person type
	$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
	$personTypeId = 0;
	if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
	{
		if (intval($arResult['ELEMENT']['COMPANY_ID']) > 0)
			$personTypeId = $arPersonTypes['COMPANY'];
		elseif (intval($arResult['ELEMENT']['CONTACT_ID']) > 0)
			$personTypeId = $arPersonTypes['CONTACT'];
	}

	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
		'',
		array(
			'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
			'FORM_ID' => $arResult['FORM_ID'],
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'OWNER_TYPE' => 'L',
			'PERMISSION_TYPE' => $enableInstantEdit && !$isExternal ? 'WRITE' : 'READ',
			'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
			'PERSON_TYPE_ID' => $personTypeId,
			'CURRENCY_ID' => $currencyID,
			'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['LOCATION_ID'] : '',
			'TOTAL_SUM' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : null,
			'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
			'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
			'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW']
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sProductsHtml = ob_get_contents();
	ob_end_clean();
}

$arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);
// <-- PRODUCT ROW SECTION

if(CModule::IncludeModule('lists'))
{
	$arResult['LIST_IBLOCK'] = CLists::getIblockAttachedCrm('LEAD');
	foreach($arResult['LIST_IBLOCK'] as $iblockId => $iblockName)
	{
		$arResult['LISTS'] = true;
		$arResult['FIELDS']['tab_lists_'.$iblockId][] = array(
			'id' => 'LEAD_LISTS_'.$iblockId,
			'name' => $iblockName,
			'colspan' => true,
			'type' => 'crm_lists_element',
			'componentData' => array(
				'template' => '',
				'enableLazyLoad' => true,
				'params' => array(
					'ENTITY_ID' => $arResult['ELEMENT']['ID'],
					'ENTITY_TYPE' => CCrmOwnerType::Lead,
					'FORM_ID' => $arResult['FORM_ID'],
					'TAB_ID' => 'tab_lists_'.$iblockId,
					'IBLOCK_ID' => $iblockId
				)
			)
		);
	}
}

if (IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
{
	$arResult['FIELDS']['tab_bizproc'][] = array(
		'id' => 'section_bizproc',
		'name' => GetMessage('CRM_SECTION_BIZPROC_MAIN'),
		'type' => 'section'
	);

	$arResult['BIZPROC'] = 'Y';

	$formTabKey = $arResult['FORM_ID'].'_active_tab';
	$activeTab = isset($_REQUEST[$formTabKey]) ? $_REQUEST[$formTabKey] : '';
	$bizprocTask = isset($_REQUEST['bizproc_task']) ? $_REQUEST['bizproc_task'] : '';
	$bizprocIndex = isset($_REQUEST['bizproc_index']) ? intval($_REQUEST['bizproc_index']) : 0;
	$bizprocAction = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

	if ($bizprocTask !== '')
	{
		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:bizproc.task',
			'',
			Array(
				'TASK_ID' => (int)$_REQUEST['bizproc_task'],
				'USER_ID' => $currentUserID,
				'WORKFLOW_ID' => '',
				'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array('lead_id' => $arResult['ELEMENT']['ID'])
				),
				'SET_TITLE' => 'Y',
				'SET_NAV_CHAIN' => 'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'LEAD_BIZPROC',
			'name' => GetMessage('CRM_FIELD_LEAD_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	elseif (isset($_REQUEST['bizproc_log']) && $_REQUEST['bizproc_log'] <> '')
	{
		ob_start();
		$APPLICATION->IncludeComponent('bitrix:bizproc.log',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentLead',
				'DOCUMENT_TYPE' => 'LEAD',
				'COMPONENT_VERSION' => 2,
				'DOCUMENT_ID' => 'LEAD_'.$arResult['ELEMENT']['ID'],
				'ID' => $_REQUEST['bizproc_log'],
				'SET_TITLE'	=>	'Y',
				'INLINE_MODE' => 'Y',
				'AJAX_MODE' => 'N'
			),
			'',
			array("HIDE_ICONS" => "Y")
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'LEAD_BIZPROC',
			'name' => GetMessage('CRM_FIELD_LEAD_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	elseif (isset($_REQUEST['bizproc_start']) && $_REQUEST['bizproc_start'] <> '')
	{
		ob_start();
		$APPLICATION->IncludeComponent('bitrix:bizproc.workflow.start',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentLead',
				'DOCUMENT_TYPE' => 'LEAD',
				'DOCUMENT_ID' => 'LEAD_'.$arResult['ELEMENT']['ID'],
				'TEMPLATE_ID' => $_REQUEST['workflow_template_id'],
				'SET_TITLE'	=>	'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'LEAD_BIZPROC',
			'name' => GetMessage('CRM_FIELD_LEAD_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	else
	{
		if(!($activeTab === 'tab_bizproc' || $bizprocIndex > 0 || $bizprocAction !== ''))
		{
			$bizprocContainerID = $arResult['BIZPROC_CONTAINER_ID'] = $arResult['FORM_ID'].'_bp_wrapper';
			$arResult['ENABLE_BIZPROC_LAZY_LOADING'] = true;
			$arResult['POST_FORM_URI'] = CHTTP::urlAddParams(POST_FORM_ACTION_URI, array($formTabKey => 'tab_bizproc'));

			$arResult['FIELDS']['tab_bizproc'][] = array(
				'id' => 'LEAD_BIZPROC',
				'name' => GetMessage('CRM_FIELD_LEAD_BIZPROC'),
				'colspan' => true,
				'type' => 'custom',
				'value' => '<div id="'.htmlspecialcharsbx($bizprocContainerID).'"></div>'
			);
		}
		else
		{
			ob_start();
			$APPLICATION->IncludeComponent('bitrix:bizproc.document',
				'',
				Array(
					'MODULE_ID' => 'crm',
					'ENTITY' => 'CCrmDocumentLead',
					'DOCUMENT_TYPE' => 'LEAD',
					'DOCUMENT_ID' => 'LEAD_'.$arResult['ELEMENT']['ID'],
					'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
						array(
							'lead_id' => $arResult['ELEMENT']['ID']
						)),
						array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
						array(
							'lead_id' => $arResult['ELEMENT']['ID']
						)),
						array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_START_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
						array(
							'lead_id' => $arResult['ELEMENT']['ID']
						)),
						array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
					),
					'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
						array(
							'lead_id' => $arResult['ELEMENT']['ID']
						)),
						array($formTabKey => 'tab_bizproc')
					),
					'SET_TITLE'	=>	'Y'
				),
				'',
				array('HIDE_ICONS' => 'Y')
			);
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_bizproc'][] = array(
				'id' => 'LEAD_BIZPROC',
				'name' => GetMessage('CRM_FIELD_LEAD_BIZPROC'),
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
		}
	}
}

if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Lead))
{
	$arResult['FIELDS']['tab_automation'][] = array(
		'id'            => 'LEAD_AUTOMATION',
		'name'          => GetMessage('CRM_SECTION_BIZPROC_MAIN'),
		'colspan'       => true,
		'type'          => 'crm_automation',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params'   => array(
				'ENTITY_TYPE_ID'     => \CCrmOwnerType::Lead,
				'ENTITY_ID'          => $arResult['ELEMENT']['ID'],
				'ENTITY_CATEGORY_ID' => null, //$arResult['CATEGORY_ID']
				'back_url'           => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array(
						'lead_id' => $arResult['ELEMENT']['ID']
					)),
					array($formTabKey => 'tab_automation')
				),
			)
		)
	);
}

$arResult['TREE_CONTAINER_ID'] = $arResult['FORM_ID'].'_tree_wrapper';
$arResult['TAB_TREE_OPEN'] = isset($_REQUEST['active_tab']) && $_REQUEST['active_tab'] == 'tab_tree';
$arResult['FIELDS']['tab_tree'] = array(array(
	'id' => 'ENTITY_TREE',
	'name' => GetMessage('CRM_FIELD_ENTITY_TREE'),
	'colspan' => true,
	'type' => 'custom',
	'value' => '<div id="'.htmlspecialcharsbx($arResult['TREE_CONTAINER_ID']).'"></div>'
));

$arResult['TAB_EVENT_TARIFF_LOCK'] = (!RestrictionManager::isHistoryViewPermitted()) ? 'Y' : 'N';

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'section_event_grid',
	'name' => GetMessage('CRM_SECTION_EVENT_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'LEAD_EVENT',
	'name' => GetMessage('CRM_FIELD_LEAD_EVENT'),
	'colspan' => true,
	'type' => 'crm_event_view',
	'componentData' => array(
		'template' => '',
		'enableLazyLoad' => true,
		'contextId' => "LEAD_{$arResult['ELEMENT']['ID']}_EVENT",
		'params' => array(
			'AJAX_OPTION_ADDITIONAL' => "LEAD_{$arResult['ELEMENT']['ID']}_EVENT",
			'ENTITY_TYPE' => 'LEAD',
			'ENTITY_ID' => $arResult['ELEMENT']['ID'],
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_event',
			'INTERNAL' => 'Y',
			'SHOW_INTERNAL_FILTER' => 'Y',
			'PRESERVE_HISTORY' => true,
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		)
	)
);

$arResult['ACTION_URI'] = $arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;

// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}

if(!isset($_REQUEST['bxajaxid']) && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
{
	CCrmEvent::RegisterViewEvent(CCrmOwnerType::Lead, $arParams['ELEMENT_ID'], $currentUserID);
}

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');
?>
