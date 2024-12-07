<?php

use Bitrix\Crm\Restriction\RestrictionManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

CUtil::InitJSCore(array('ajax'));
Bitrix\Main\UI\Extension::load("ui.tooltip");

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if($arResult['ELEMENT_ID'] <= 0)
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CATEGORY_ID'] = CCrmDeal::GetCategoryID($arResult['ELEMENT_ID']);
if(!CCrmDeal::CheckReadPermission($arResult['ELEMENT_ID'], $userPermissions, $arResult['CATEGORY_ID']))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['PERMISSION_ENTITY_TYPE'] = \Bitrix\Crm\Category\DealCategory::convertToPermissionEntityType($arResult['CATEGORY_ID']);

$arResult['EDITABLE_FIELDS'] = array();
$arResult['CAN_EDIT'] = CCrmDeal::CheckUpdatePermission($arResult['ELEMENT_ID'], $userPermissions);

CCrmDeal::PrepareConversionPermissionFlags($arResult['ELEMENT_ID'], $arResult, $userPermissions);

if($arResult['CAN_CONVERT'])
{
	$config = \Bitrix\Crm\Conversion\DealConversionConfig::load();
	if($config === null)
	{
		$config = \Bitrix\Crm\Conversion\DealConversionConfig::getDefault();
	}

	$arResult['CONVERSION_CONFIG'] = $config;
}

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arResult['PATH_TO_DEAL_SHOW'] = $arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');

$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

global $USER_FIELD_MANAGER;
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);

$obFields = CCrmDeal::GetListEx(
	array(),
	array(
	'ID' => $arParams['ELEMENT_ID']
	)
);
$arFields = $obFields->GetNext();

$arFields['CONTACT_FM'] = array();
if(isset($arFields['CONTACT_ID']) && intval($arFields['CONTACT_ID']) > 0)
{
	$dbResMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arFields['CONTACT_ID'])
	);
	while($arMultiFields = $dbResMultiFields->Fetch())
	{
		$arFields['CONTACT_FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
	}
}

$arFields['COMPANY_FM'] = array();
if(isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
{
	$dbResMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arFields['COMPANY_ID'])
	);
	while($arMultiFields = $dbResMultiFields->Fetch())
	{
		$arFields['COMPANY_FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
	}
}

$arResult['STAGE_LIST'] = Bitrix\Crm\Category\DealCategory::getStageList(
	isset($arFields['~CATEGORY_ID']) ? (int)$arFields['~CATEGORY_ID'] : 0
);
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['STATE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STATE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_TYPE');
$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
//$arResult['PRODUCT_ROWS'] = CCrmDeal::LoadProductRows($arParams['ELEMENT_ID']);

$arFields['TYPE_TEXT'] = isset($arFields['TYPE_ID'])
	&& isset($arResult['TYPE_LIST'][$arFields['TYPE_ID']])
	? $arResult['TYPE_LIST'][$arFields['TYPE_ID']] : '';

$arFields['~STAGE_TEXT'] = isset($arFields['STAGE_ID'])
	&& isset($arResult['STAGE_LIST'][$arFields['STAGE_ID']])
	? $arResult['STAGE_LIST'][$arFields['STAGE_ID']] : '';

$arFields['STAGE_TEXT'] = htmlspecialcharsbx($arFields['~STAGE_TEXT']);

$arContactType = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
$arFields['CONTACT_TYPE_TEXT'] = isset($arFields['CONTACT_TYPE_ID'])
	&& isset($arContactType[$arFields['CONTACT_TYPE_ID']])
	? $arContactType[$arFields['CONTACT_TYPE_ID']] : '';

$arContactSource = CCrmStatus::GetStatusListEx('SOURCE');
$arFields['CONTACT_SOURCE_TEXT'] = isset($arFields['CONTACT_SOURCE_ID'])
	&& isset($arContactSource[$arFields['CONTACT_SOURCE_ID']])
	? $arContactSource[$arFields['CONTACT_SOURCE_ID']] : '';

$arFields['~CONTACT_FORMATTED_NAME'] = CCrmContact::PrepareFormattedName(
	array(
		'HONORIFIC' => isset($arFields['~CONTACT_HONORIFIC']) ? $arFields['~CONTACT_HONORIFIC'] : '',
		'NAME' => isset($arFields['~CONTACT_NAME']) ? $arFields['~CONTACT_NAME'] : '',
		'LAST_NAME' => isset($arFields['~CONTACT_LAST_NAME']) ? $arFields['~CONTACT_LAST_NAME'] : '',
		'SECOND_NAME' => isset($arFields['~CONTACT_SECOND_NAME']) ? $arFields['~CONTACT_SECOND_NAME'] : ''
	)
);
$arFields['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~CONTACT_FORMATTED_NAME']);
//Setup contacts and = count only. Primary contact is in CONTACT_* fields
$arFields['CONTACT_IDS'] = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($arResult['ELEMENT_ID']);
$arFields['CONTACT_COUNT'] = count($arFields['CONTACT_IDS']);

if($currentUserID > 0)
{
	$entitySettings = \Bitrix\Crm\Config\EntityConfig::get(
		CCrmOwnerType::Deal,
		$arResult['ELEMENT_ID'],
		$currentUserID
	);

	$selectedContactID = is_array($entitySettings) && isset($entitySettings['CONTACT_ID'])
		? (int)$entitySettings['CONTACT_ID'] : 0;

	if($selectedContactID > 0)
	{
		$selectedContactIndex = array_search($selectedContactID, $arFields['CONTACT_IDS'], true);
		if(is_int($selectedContactIndex))
		{
			$contactDbResult = CCrmContact::GetListEx(
				array(),
				array('=ID' => $selectedContactID),
				false,
				false,
				array(
					'ID', 'TYPE_ID', 'SOURCE_ID',
					'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME',
					'POST', 'PHOTO'
				)
			);

			$selectedContactFields = is_object($contactDbResult) ? $contactDbResult->Fetch() : null;
			if(is_array($selectedContactFields))
			{
				$selectedContactFields['FORMATTED_NAME'] = CCrmContact::PrepareFormattedName($selectedContactFields);
				$arFields['SELECTED_CONTACT_INDEX'] = $selectedContactIndex;
				$arFields['SELECTED_CONTACT'] = $selectedContactFields;
			}
		}
	}
}

$arCompanyIndustry = CCrmStatus::GetStatusListEx('INDUSTRY');
$arFields['COMPANY_INDUSTRY_TEXT'] = isset($arFields['COMPANY_INDUSTRY'])
	&& isset($arCompanyIndustry[$arFields['COMPANY_INDUSTRY']])
	? $arCompanyIndustry[$arFields['COMPANY_INDUSTRY']] : '';

$arCompanyEmployees = CCrmStatus::GetStatusListEx('EMPLOYEES');
$arFields['COMPANY_EMPLOYEES_TEXT'] = isset($arFields['COMPANY_EMPLOYEES'])
	&& isset($arCompanyEmployees[$arFields['COMPANY_EMPLOYEES']])
	? $arCompanyEmployees[$arFields['COMPANY_EMPLOYEES']] : '';

$arCompanyType = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
$arFields['COMPANY_TYPE_TEXT'] = isset($arFields['COMPANY_TYPE'])
	&& isset($arCompanyType[$arFields['COMPANY_TYPE']])
	? $arCompanyType[$arFields['COMPANY_TYPE']] : '';

$companyLogoID = isset($arFields['~COMPANY_LOGO']) ? intval($arFields['~COMPANY_LOGO']) : 0;
if($companyLogoID <= 0)
{
	$arFields['COMPANY_LOGO_HTML'] = '';
}
else
{
	$arPhoto = CFile::ResizeImageGet(
		$companyLogoID,
		array('width' => 50, 'height' => 50),
		BX_RESIZE_IMAGE_PROPORTIONAL,
		false
	);
	$arFields['COMPANY_LOGO_HTML'] = CFile::ShowImage($arPhoto['src'], 50, 50, 'border=0');
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

$arFields['ORIGIN_ID'] = isset($arFields['ORIGIN_ID']) ? intval($arFields['ORIGIN_ID']) : 0;
$arFields['ORIGINATOR_ID'] = isset($arFields['ORIGINATOR_ID']) ? intval($arFields['ORIGINATOR_ID']) : 0;

$arResult['ELEMENT'] = $arFields;

if ($arParams['IS_RECURRING'] === "Y")
{
	if ($arFields['IS_RECURRING'] !== "Y")
	{
		LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_RECUR'], array()));
	}

	$recurData = Bitrix\Crm\DealRecurTable::getList(
		array(
			"filter" => array("=DEAL_ID" => $arParams['ELEMENT_ID'])
		)
	);
	$arResult['RECURRING_DATA'] = $recurData->fetch();
}
elseif ($arFields['IS_RECURRING'] === "Y")
{
	LocalRedirect(CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
}
unset($arFields);

if (empty($arResult['ELEMENT']['ID']))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
}

$contactID = isset($arResult['ELEMENT']['CONTACT_ID']) ? intval($arResult['ELEMENT']['CONTACT_ID']) : 0;
$companyID = isset($arResult['ELEMENT']['COMPANY_ID']) ? intval($arResult['ELEMENT']['COMPANY_ID']) : 0;

$currentUserPermissions =  CCrmPerms::GetCurrentUserPermissions();

$arResult['ERROR_MESSAGE'] = '';

if (intval($_REQUEST["SYNC_ORDER_ID"]) > 0)
{
	$imp = new CCrmExternalSaleImport($arResult['ELEMENT']["ORIGINATOR_ID"]);
	if ($imp->IsInitialized())
	{
		$r = $imp->GetOrderData($arResult['ELEMENT']["ORIGIN_ID"], false);
		if ($r != CCrmExternalSaleImport::SyncStatusError)
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $arResult['ELEMENT']['ID'])));
		}
		else
		{
			$arErrors = $imp->GetErrors();
			foreach ($arErrors as $err)
				$arResult['ERROR_MESSAGE'] .= $err[1]."<br />";
		}
	}
}

$isExternal = $arResult['IS_EXTERNAL'] = $arResult['ELEMENT']['ORIGINATOR_ID'] > 0 && $arResult['ELEMENT']['ORIGIN_ID'] > 0;
if($isExternal)
{
	$dbSalesList = CCrmExternalSale::GetList(
		array(
			'NAME' => 'ASC',
			'SERVER' => 'ASC'
		),
		array('ID' =>  $arResult['ELEMENT']['ORIGINATOR_ID'])
	);

	$arExternalSale = $dbSalesList->Fetch();
	if(is_array($arExternalSale))
	{
		$arResult['EXTERNAL_SALE_INFO'] = array(
			'ID' =>  $arResult['ELEMENT']['ORIGINATOR_ID'],
			'NAME' => $arExternalSale['NAME'],
			'SERVER' => $arExternalSale['SERVER'],
		);
	}
}

$arResult['FORM_ID'] = 'CRM_DEAL_SHOW_V12'.($isExternal ? "_E" : "");
$arResult['GRID_ID'] = 'CRM_DEAL_LIST_V12'.($isExternal ? "_E" : "");
$arResult['PRODUCT_ROW_TAB_ID'] = 'tab_product_rows';
$arResult['BACK_URL'] = $arParams['PATH_TO_DEAL_LIST'];

$arResult['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
	array(
		'company_id' => $arResult['ELEMENT']['COMPANY_ID']
	)
);
$arResult['PATH_TO_CONTACT_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
	array(
		'contact_id' => $arResult['ELEMENT']['CONTACT_ID']
	)
);
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];

$arResult['TACTILE_FORM_ID'] = Bitrix\Crm\Category\DealCategory::prepareFormID(
	$arResult['ELEMENT']['~CATEGORY_ID'],
	'CRM_DEAL_EDIT_V12'
);

$arResult['FIELDS'] = array();

$readOnlyMode = !$enableInstantEdit || $isExternal;

$arResult['FIELDS']['tab_1'] = array();

// DEAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_deal_info',
	'name' => GetMessage('CRM_SECTION_DEAL'),
	'type' => 'section',
	'isTactile' => true
);


// TITLE -->
// TITLE is displayed in summary panel. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TITLE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'label',
	'isTactile' => true
);
// <-- TITLE

// STAGE -->
// STAGE is displayed in summary panel. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'STAGE_ID';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STAGE_ID',
	'name' => GetMessage('CRM_FIELD_STAGE_ID'),
	'type' => 'label',
	'value' => $arResult['ELEMENT']['~STAGE_TEXT'],
	'isTactile' => true
);
// <-- STAGE

// CURRENCY -->
$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'type' => 'label',
	'value' => CCrmCurrency::GetCurrencyName($currencyID),
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

// PROBABILITY -->
// PROBABILITY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'PROBABILITY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PROBABILITY',
	'name' => GetMessage('CRM_FIELD_PROBABILITY'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~PROBABILITY']) ? $arResult['ELEMENT']['~PROBABILITY'] : '',
	'isTactile' => true
);
// <-- PROBABILITY

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

if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'BEGINDATE';
}

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_FIELD_BEGINDATE_1'),
	'params' => array('size' => 20),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['BEGINDATE']), 'SHORT', SITE_ID)) : '',
	'isTactile' => true
);

if ($arParams['IS_RECURRING'] !== 'Y')
{
	if($enableInstantEdit)
	{
		$arResult['EDITABLE_FIELDS'][] = 'CLOSEDATE';
	}

	$arResult['FIELDS']['tab_details'][] = array(
		'id' => 'CLOSEDATE',
		'name' => GetMessage('CRM_FIELD_CLOSEDATE'),
		'params' => array('size' => 20),
		'type' => 'label',
		'value' => !empty($arResult['ELEMENT']['CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['CLOSEDATE']), 'SHORT', SITE_ID)) : '',
		'isTactile' => true
	);
}
elseif (!empty($arResult['RECURRING_DATA']))
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'RECURRING_ACTIVE',
		'name' => GetMessage('CRM_FIELD_RECURRING_ACTIVE'),
		'type' => 'label',
		'value' => $arResult['RECURRING_DATA']['ACTIVE'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
		'isTactile' => true
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'RECURRING_NEXT_EXECUTION',
		'name' => GetMessage('CRM_FIELD_RECURRING_NEXT_EXECUTION'),
		'type' => 'label',
		'value' => ($arResult['RECURRING_DATA']['NEXT_EXECUTION']) instanceof \Bitrix\Main\Type\Date? $arResult['RECURRING_DATA']['NEXT_EXECUTION']->toString()  : '',
		'isTactile' => true
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'RECURRING_LAST_EXECUTION',
		'name' => GetMessage('CRM_FIELD_RECURRING_LAST_EXECUTION'),
		'type' => 'label',
		'value' => ($arResult['RECURRING_DATA']['LAST_EXECUTION']) instanceof \Bitrix\Main\Type\Date? $arResult['RECURRING_DATA']['LAST_EXECUTION']->toString()  : '',
		'isTactile' => true
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'RECURRING_COUNTER_REPEAT',
		'name' => GetMessage('CRM_FIELD_RECURRING_COUNTER_REPEAT'),
		'type' => 'label',
		'value' => (int)($arResult['RECURRING_DATA']['COUNTER_REPEAT']) > 0 ? (int)($arResult['RECURRING_DATA']['COUNTER_REPEAT']) : 0,
		'isTactile' => true
	);
}

// TYPE -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TYPE_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'label',
	'items' => $arResult['TYPE_LIST'],
	'value' => $arResult['ELEMENT']['TYPE_TEXT'],
	'isTactile' => true
);
// <-- TYPE

// CLOSED -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSED',
	'name' => GetMessage('CRM_FIELD_CLOSED'),
	'type' => 'label',
	'value' => (isset($arResult['ELEMENT']['CLOSED']) ? ($arResult['ELEMENT']['CLOSED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')) : GetMessage('MAIN_NO')),
	'isTactile' => true
);
// <-- CLOSED

// OPENED -->
// OPENED is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPENED';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'label',
	'params' => array(),
	'value' => $arResult['ELEMENT']['OPENED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
	'isTactile' => true
);
// <-- OPENED

// COMMENTS -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'COMMENTS';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'params' => array(),
	'isTactile' => true
);
// <-- COMMENTS


// <-- DEAL SECTION

// CONTACT INFO SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO'),
	'type' => 'section',
	'isTactile' => true
);


if(CCrmCompany::CheckReadPermission(0, $userPermissions) || CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$companyID = isset($arResult['ELEMENT']['COMPANY_ID']) ? (int)$arResult['ELEMENT']['COMPANY_ID'] : 0;
	$contactBindings = \Bitrix\Crm\Binding\DealContactTable::getDealBindings($arParams['ELEMENT_ID']);
	if($companyID > 0 || empty($contactBindings))
	{
		$primaryEntityTypeName = CCrmOwnerType::CompanyName;
		$primaryEntityID = $companyID;
	}
	else
	{
		$primaryEntityTypeName = CCrmOwnerType::ContactName;
		$primaryBinding = \Bitrix\Crm\Binding\EntityBinding::findPrimaryBinding($contactBindings);
		if($primaryBinding === null)
		{
			$primaryBinding = $contactBindings[0];
		}
		$primaryEntityID = $primaryBinding['CONTACT_ID'];
	}

	$requisiteIdLinked = 0;
	$bankDetailIdLinked = 0;
	$requisteBindings = Bitrix\Crm\Requisite\EntityLink::getByEntity(CCrmOwnerType::Deal, $arParams['ELEMENT_ID']);
	if(is_array($requisteBindings))
	{
		if(isset($requisteBindings['REQUISITE_ID']))
		{
			$requisiteIdLinked = (int)$requisteBindings['REQUISITE_ID'];
		}

		if(isset($requisteBindings['BANK_DETAIL_ID']))
		{
			$bankDetailIdLinked = (int)$requisteBindings['BANK_DETAIL_ID'];
		}
	}

	$arResult['CLIENT_SELECTOR_ID'] = 'CLIENT';
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => $arResult['CLIENT_SELECTOR_ID'],
		'name' => GetMessage('CRM_DEAL_SHOW_FIELD_CLIENT'),
		'type' => 'crm_composite_client_selector',
		'componentParams' => array(
			'CONTEXT' => "DEAL_{$arParams['ELEMENT_ID']}",
			'OWNER_TYPE' => CCrmOwnerType::DealName,
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'READ_ONLY' => true,
			'PRIMARY_ENTITY_TYPE' => $primaryEntityTypeName,
			'PRIMARY_ENTITY_ID' => $primaryEntityID,
			'SECONDARY_ENTITY_TYPE' => CCrmOwnerType::ContactName,
			'SECONDARY_ENTITY_IDS' => \Bitrix\Crm\Binding\EntityBinding::prepareEntityIDs(CCrmOwnerType::Contact, $contactBindings),
			'CUSTOM_MESSAGES' => array(
				'SECONDARY_ENTITY_HEADER' => GetMessage('CRM_DEAL_SHOW_CONTACT_SELECTOR_HEADER')
			),
			'REQUISITE_ID' => $requisiteIdLinked,
			'BANK_DETAIL_ID' => $bankDetailIdLinked,
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat(),
			'ENTITY_SELECTOR_SEARCH_OPTIONS' => array(
				'NOT_MY_COMPANIES' => 'Y'
			)
		),
		'isTactile' => true
	);
}
// <-- CONTACT INFO SECTION

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

// QUOTE TITLE
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'QUOTE_ID',
	'name' => GetMessage('CRM_FIELD_DEAL_QUOTE'),
	'value' => isset($arResult['ELEMENT']['QUOTE_TITLE'])
		? (!CCrmQuote::CheckReadPermission(0, $userPermissions)
			? $arResult['ELEMENT']['QUOTE_TITLE'] :
			'<a href="'.$arResult['PATH_TO_QUOTE_SHOW'].'" bx-tooltip-user-id="QUOTE_'.$arResult['ELEMENT']['~QUOTE_ID'].'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.quote.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_quote">'.$arResult['ELEMENT']['QUOTE_TITLE'].'</a>'
		) : '',
	'type' => 'custom',
	'isTactile' => true
);

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

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_CREATE',
	'name' => GetMessage('CRM_FIELD_DATE_CREATE'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['DATE_CREATE']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_CREATE']), (time() + CTimeZone::GetOffset())) : ''
);

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

$arResult['USER_FIELD_COUNT'] = $CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	false,
	true,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		'IS_TACTILE' => true
	)
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.interface.form.recurring',
	'show',
	array(
		'DATA' => $arResult['RECURRING_DATA']['PARAMS'],
		'IS_RECURRING' => $arParams['IS_RECURRING'],
		'ENTITY_TYPE' => Bitrix\Crm\Recurring\Manager::DEAL,
		'PATH_TO_DEAL_EDIT' => CComponentEngine::makePathFromTemplate($arParams['PATH_TO_DEAL_EDIT']."#section_recurring", array('deal_id' => $arParams['ELEMENT_ID']))
	),
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$recurringHtml = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_recurring',
	'name' => GetMessage('CRM_SECTION_RECURRING_ROWS'),
	'type' => 'section',
	'isTactile' => true,
	'isHidden' => $arParams['IS_RECURRING'] !== 'Y'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_recurring_rows',
	'name' => GetMessage('CRM_SECTION_RECURRING_ROWS'),
	'params' =>
		array (
			'class' => 'bx-crm-dialog-input bx-crm-dialog-input-date',
			'sale_order_marker' => 'Y',
		),
	'type' => 'recurring_params',
	'colspan' => true,
	'value' => $recurringHtml,
	'isTactile' => true,
	'isHidden' => $arParams['IS_RECURRING'] !== 'Y'
);

// <-- ADDITIONAL SECTION

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'section_details',
	'name' => GetMessage('CRM_SECTION_DETAILS'),
	'type' => 'section'
);

// WEB-STORE SECTION -->
$enableWebStore = true;
$strEditOrderHtml = '';
if($isExternal)
{
	if(isset($arResult['EXTERNAL_SALE_INFO']))
	{
		$strEditOrderHtml .= $arResult['EXTERNAL_SALE_INFO']['NAME'] != ''
			? htmlspecialcharsbx($arResult['EXTERNAL_SALE_INFO']['NAME'])
			: htmlspecialcharsbx($arResult['EXTERNAL_SALE_INFO']['SERVER']);
	}
	else
	{
		$strEditOrderHtml .= GetMessage("CRM_EXTERNAL_SALE_NOT_FOUND");
	}
}
else
{
	$dbSalesList = CCrmExternalSale::GetList(
		array(),
		array("ACTIVE" => "Y")
	);

	$enableWebStore = $dbSalesList->Fetch() !== false;
}

if($enableWebStore)
{
	$arResult['FIELDS']['tab_details'][] = array(
		'id' => 'section_web_store',
		'name' => GetMessage('CRM_SECTION_WEB_STORE'),
		'type' => 'section',
		'isTactile' => true
	);

	$arResult['FIELDS']['tab_details'][] = array(
		'id' => 'SALE_ORDER',
		'name' => GetMessage('CRM_FIELD_SALE_ORDER1'),
		'type' => 'custom',
		'value' => isset($strEditOrderHtml[0]) ? $strEditOrderHtml : htmlspecialcharsbx(GetMessage('MAIN_NO')),
		'isTactile' => true
	);
}
// <-- WEB-STORE SECTION
if($enableWebStore)
{
	$strAdditionalInfoHtml = '';
	if ($isExternal &&  isset($arResult['ELEMENT']['ADDITIONAL_INFO']))
	{
		$arAdditionalInfo = unserialize($arResult['ELEMENT']['~ADDITIONAL_INFO'], ['allowed_classes' => false]);
		if (is_array($arAdditionalInfo) && count($arAdditionalInfo) > 0)
		{
			foreach ($arAdditionalInfo as $k => $v)
			{
				$label = GetMessage("CRM_SALE_{$k}");
				if(!(is_string($label) && $label !== ''))
				{
					$label = $k;
				}

				if (is_bool($v))
				{
					$v = $v ? GetMessage('CRM_SALE_YES') : GetMessage('CRM_SALE_NO');
				}

				$strAdditionalInfoHtml .= '<span>'.htmlspecialcharsbx($label).'</span>: <span>'.htmlspecialcharsbx($v).'</span><br/>';
			}
		}
	}

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDITIONAL_INFO',
		'name' => GetMessage('CRM_FIELD_ADDITIONAL_INFO'),
		'type' => 'custom',
		'value' => isset($strAdditionalInfoHtml[0]) ? $strAdditionalInfoHtml : htmlspecialcharsbx(GetMessage('MAIN_NO')),
		'isTactile' => true
	);
}

// PRODUCT ROW SECTION -->
$arResult['FIELDS'][$arResult['PRODUCT_ROW_TAB_ID']][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);
$APPLICATION->AddHeadScript($this->GetPath().'/sale.js');

$sProductsHtml = '<script>var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>';

if ($isExternal)
{
	if(isset($arResult['EXTERNAL_SALE_INFO']))
	{
		$sProductsHtml .= '<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'EDIT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_EDIT").'</span>'.
			'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'VIEW\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_VIEW").'</span>'.
			'<span class="webform-small-button webform-small-button-accept" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'PRINT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">'.GetMessage("CRM_EXT_SALE_CD_PRINT").'</span><br/><br/>';
	}
	else
	{
		$sProductsHtml .= GetMessage("CRM_EXTERNAL_SALE_NOT_FOUND");
	}
}

$arResult['PRODUCT_ROW_EDITOR_ID'] = 'deal_'.strval($arParams['ELEMENT_ID']).'_product_editor';
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
			'OWNER_TYPE' => 'D',
			'PERMISSION_TYPE' => $enableInstantEdit && !$isExternal ? 'WRITE' : 'READ',
			'PERMISSION_ENTITY_TYPE' => $arResult['PERMISSION_ENTITY_TYPE'],
			'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
			'PERSON_TYPE_ID' => $personTypeId,
			'CURRENCY_ID' => $currencyID,
			'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['LOCATION_ID'] : '',
			'CLIENT_SELECTOR_ID' => $arResult['CLIENT_SELECTOR_ID'],
			'TOTAL_SUM' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : null,
			'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
			'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
			'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW']
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sProductsHtml .= ob_get_contents();
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

if ($arParams['IS_RECURRING'] !== 'Y' && \Bitrix\Crm\Integration\Socialnetwork\Livefeed\AvailabilityHelper::isAvailable())
{
	// LIVE FEED SECTION -->

	$arResult['FIELDS']['tab_live_feed'][] = array(
		'id' => 'section_live_feed',
		'name' => GetMessage('CRM_SECTION_LIVE_FEED'),
		'type' => 'section'
	);

	$liveFeedHtml = '';
	if($arParams['ELEMENT_ID'] > 0)
	{
		if(CCrmLiveFeedComponent::needToProcessRequest($_SERVER['REQUEST_METHOD'], $_REQUEST))
		{
			ob_start();
			$APPLICATION->IncludeComponent('bitrix:crm.entity.livefeed',
				'',
				array(
					'DATE_TIME_FORMAT' => (LANGUAGE_ID=='en'?"j F Y g:i a":(LANGUAGE_ID=='de' ? "j. F Y, G:i" : "j F Y G:i")),
					'CAN_EDIT' => $arResult['CAN_EDIT'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
					'ENTITY_ID' => $arParams['ELEMENT_ID'],
					'PERMISSION_ENTITY_TYPE' => $arResult['PERMISSION_ENTITY_TYPE'],
					'FORM_ID' => $arResult['FORM_ID'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE']
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
			$liveFeedHtml = ob_get_contents();
			ob_end_clean();
			$arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] = false;
		}
		else
		{
			$liveFeedContainerID = $arResult['LIVE_FEED_CONTAINER_ID'] = $arResult['FORM_ID'].'_live_feed_wrapper';
			$liveFeedHtml = '<div id="'.htmlspecialcharsbx($liveFeedContainerID).'"></div>';
			$arResult['ENABLE_LIVE_FEED_LAZY_LOAD'] = true;
		}
	}

	$arResult['FIELDS']['tab_live_feed'][] = array(
		'id' => 'LIVE_FEED',
		'name' => GetMessage('CRM_FIELD_LIVE_FEED'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $liveFeedHtml
	);
// <-- LIVE FEED SECTION
}


if ($arParams['IS_RECURRING'] !== 'Y')
{
	$arResult['FIELDS']['tab_activity'][] = array(
		'id' => 'section_activity_grid',
		'name' => GetMessage('CRM_SECTION_ACTIVITY_MAIN'),
		'type' => 'section'
	);

	$activityBindings = array(array('TYPE_NAME' => CCrmOwnerType::DealName, 'ID' => $arParams['ELEMENT_ID']));
	if(isset($arResult['ELEMENT']['LEAD_ID']) && $arResult['ELEMENT']['LEAD_ID'] > 0)
	{
		$activityBindings[] = array('TYPE_NAME' => CCrmOwnerType::LeadName, 'ID' => $arResult['ELEMENT']['LEAD_ID']);
	}

	$arResult['FIELDS']['tab_activity'][] = array(
		'id' => 'DEAL_ACTIVITY_GRID',
		'name' => GetMessage('CRM_FIELD_DEAL_ACTIVITY'),
		'colspan' => true,
		'type' => 'crm_activity_list',
		'componentData' => array(
			'template' => 'grid',
			'enableLazyLoad' => true,
			'params' => array(
				'BINDINGS' => $activityBindings,
				'PREFIX' => 'DEAL_ACTIONS_GRID',
				'PERMISSION_TYPE' => 'WRITE',
				'FORM_TYPE' => 'show',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_activity',
				'USE_QUICK_FILTER' => 'Y',
				'PRESERVE_HISTORY' => true
			)
		)
	);
	$formTabKey = $arResult['FORM_ID'].'_active_tab';
	$currentFormTabID = $_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET[$formTabKey]) ? $_GET[$formTabKey] : '';
}

if (!empty($arResult['ELEMENT']['CONTACT_IDS']))
{
	$arResult['FIELDS']['tab_contact'][] = array(
		'id' => 'DEAL_CONTACTS',
		'name' => GetMessage('CRM_FIELD_DEAL_CONTACTS'),
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
				'INTERNAL_FILTER' => array('ASSOCIATED_DEAL_ID' => $arResult['ELEMENT']['ID']),
				'GRID_ID_SUFFIX' => 'DEAL_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_contact',
				'PRESERVE_HISTORY' => true
			)
		)
	);
}

if ($companyID > 0 && CCrmCompany::CheckReadPermission($companyID, $currentUserPermissions))
{
	$arResult['FIELDS']['tab_company'][] = array(
		'id' => 'DEAL_COMPANY',
		'name' => GetMessage('CRM_FIELD_DEAL_COMPANY'),
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
				'INTERNAL_FILTER' => array('ID' => $companyID),
				'GRID_ID_SUFFIX' => 'DEAL_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_company',
				'PRESERVE_HISTORY' => true,
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			)
		)
	);
}


$quoteID = isset($arResult['ELEMENT']['QUOTE_ID']) ? (int)$arResult['ELEMENT']['QUOTE_ID'] : 0;
if (CCrmQuote::CheckReadPermission($quoteID, $userPermissions) && $arParams['IS_RECURRING'] !== 'Y')
{
	if ($quoteID > 0)
	{
		$dbResQuote = CCrmQuote::GetList(
			array(),
			array('ID' => $arResult['ELEMENT']['QUOTE_ID'], 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('TITLE')
		);
		$quoteFields = is_object($dbResQuote) ? $dbResQuote->Fetch() : null;
		if (is_array($quoteFields))
		{
			$arResult['FIELDS']['tab_quote'][] = array(
				'id' => 'DEAL_QUOTE',
				'name' => GetMessage('CRM_FIELD_DEAL_QUOTE_LIST'),
				'colspan' => true,
				'type' => 'custom',
				'value' => '<div class="crm-conv-info">'
					.GetMessage(
						'CRM_DEAL_QUOTE_LINK_MSGVER_1',
						array(
							'#TITLE#' => htmlspecialcharsbx($quoteFields['TITLE']),
							'#URL#' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Quote, $arResult['ELEMENT']['QUOTE_ID'], false)
						)
					)
					.'</div>'
			);
		}
	}
	else
	{
		$arResult['FIELDS']['tab_quote'][] = array(
			'id' => 'DEAL_QUOTE',
			'name' => GetMessage('CRM_FIELD_DEAL_QUOTE_LIST'),
			'colspan' => true,
			'type' => 'crm_quote_list',
			'componentData' => array(
				'template' => '',
				'enableLazyLoad' => true,
				'params' => array(
					'QUOTE_COUNT' => '20',
					'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'],
					'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'],
					'INTERNAL_FILTER' => array('DEAL_ID' => $arResult['ELEMENT']['ID']),
					'INTERNAL_CONTEXT' => array('DEAL_ID' => $arResult['ELEMENT']['ID']),
					'GRID_ID_SUFFIX' => 'DEAL_SHOW',
					'FORM_ID' => $arResult['FORM_ID'],
					'TAB_ID' => 'tab_quote',
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
					'ENABLE_TOOLBAR' => true,
					'PRESERVE_HISTORY' => true,
					'ADD_EVENT_NAME' => 'CrmCreateQuoteFromDeal'
				)
			)
		);
	}
}

if (CCrmInvoice::CheckReadPermission(0, $userPermissions) && $arParams['IS_RECURRING'] !== 'Y')
{
	$arResult['FIELDS']['tab_invoice'][] = array(
		'id' => 'DEAL_INVOICE',
		'name' => GetMessage('CRM_FIELD_DEAL_INVOICE'),
		'colspan' => true,
		'type' => 'crm_invoice_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'INVOICE_COUNT' => '20',
				'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
				'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'PATH_TO_INVOICE_EDIT' => $arParams['PATH_TO_INVOICE_EDIT'],
				'PATH_TO_INVOICE_PAYMENT' => $arParams['PATH_TO_INVOICE_PAYMENT'],
				'INTERNAL_FILTER' => array('UF_DEAL_ID' => $arResult['ELEMENT']['ID']),
				'SUM_PAID_CURRENCY' => $currencyID,
				'GRID_ID_SUFFIX' => 'DEAL_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_invoice',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => 'Y',
				'PRESERVE_HISTORY' => true,
				'ADD_EVENT_NAME' => 'CrmCreateInvoiceFromDeal',
				'INTERNAL_ADD_BTN_TITLE' => GetMessage('CRM_DEAL_ADD_INVOICE_TITLE')
			)
		)
	);
}

if(CModule::IncludeModule('lists'))
{
	$arResult['LIST_IBLOCK'] = CLists::getIblockAttachedCrm('DEAL');
	foreach($arResult['LIST_IBLOCK'] as $iblockId => $iblockName)
	{
		$arResult['LISTS'] = true;
		$arResult['FIELDS']['tab_lists_'.$iblockId][] = array(
			'id' => 'DEAL_LISTS_'.$iblockId,
			'name' => $iblockName,
			'colspan' => true,
			'type' => 'crm_lists_element',
			'componentData' => array(
				'template' => '',
				'enableLazyLoad' => true,
				'params' => array(
					'ENTITY_ID' => $arResult['ELEMENT']['ID'],
					'ENTITY_TYPE' => CCrmOwnerType::Deal,
					'FORM_ID' => $arResult['FORM_ID'],
					'TAB_ID' => 'tab_lists_'.$iblockId,
					'IBLOCK_ID' => $iblockId
				)
			)
		);
	}
}

if (IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled() && $arParams['IS_RECURRING'] !== 'Y')
{
	//HACK: main.interface.grid may override current tab
	if($_SERVER['REQUEST_METHOD'] === 'GET' && $currentFormTabID !== '')
	{
		$_GET[$formTabKey] = $currentFormTabID;
	}

	$arResult['FIELDS']['tab_bizproc'][] = array(
		'id' => 'section_bizproc',
		'name' => GetMessage('CRM_SECTION_BIZPROC_MAIN'),
		'type' => 'section'
	);

	$arResult['BIZPROC'] = 'Y';

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
				'USER_ID' => 0,
				'WORKFLOW_ID' => '',
				'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
					array(
						'deal_id' => $arResult['ELEMENT']['ID']
					)
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
			'id' => 'DEAL_BIZPROC',
			'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
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
				'ENTITY' => 'CCrmDocumentDeal',
				'DOCUMENT_TYPE' => 'DEAL',
				'COMPONENT_VERSION' => 2,
				'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
				'ID' => $_REQUEST['bizproc_log'],
				'SET_TITLE'	=>	'Y',
				'INLINE_MODE' => 'Y',
				'AJAX_MODE' => 'N',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			),
			'',
			array("HIDE_ICONS" => "Y")
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'DEAL_BIZPROC',
			'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
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
				'ENTITY' => 'CCrmDocumentDeal',
				'DOCUMENT_TYPE' => 'DEAL',
				'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
				'TEMPLATE_ID' => $_REQUEST['workflow_template_id'],
				'SET_TITLE'	=>	'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'DEAL_BIZPROC',
			'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
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
			$arResult['FIELDS']['tab_bizproc'][] = array(
				'id' => 'DEAL_BIZPROC',
				'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
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
					'ENTITY' => 'CCrmDocumentDeal',
					'DOCUMENT_TYPE' => 'DEAL',
					'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
					'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
							)),
						array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
							)),
						array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_START_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
							)),
						array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
					),
					'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
							array(
								'deal_id' => $arResult['ELEMENT']['ID']
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
				'id' => 'DEAL_BIZPROC',
				'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
		}
	}
}

if (\Bitrix\Crm\Automation\Factory::isAutomationAvailable(CCrmOwnerType::Deal) && $arParams['IS_RECURRING'] !== 'Y')
{
	$arResult['FIELDS']['tab_automation'][] = array(
		'id'            => 'DEAL_AUTOMATION',
		'name'          => GetMessage('CRM_SECTION_BIZPROC_MAIN'),
		'colspan'       => true,
		'type'          => 'crm_automation',
		'componentData' => array(
			'template'       => '',
			'enableLazyLoad' => true,
			'params'         => array(
				'ENTITY_TYPE_ID'     => \CCrmOwnerType::Deal,
				'ENTITY_ID'          => $arResult['ELEMENT']['ID'],
				'ENTITY_CATEGORY_ID' => $arResult['CATEGORY_ID'],
				'back_url'           => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
					array(
						'deal_id' => $arResult['ELEMENT']['ID']
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
	'id' => 'DEAL_EVENT',
	'name' => GetMessage('CRM_FIELD_DEAL_EVENT'),
	'colspan' => true,
	'type' => 'crm_event_view',
	'componentData' => array(
		'template' => '',
		'enableLazyLoad' => true,
		'contextId' => "DEAL_{$arResult['ELEMENT']['ID']}_EVENT",
		'params' => array(
			'AJAX_OPTION_ADDITIONAL' => "DEAL_{$arResult['ELEMENT']['ID']}_EVENT",
			'ENTITY_TYPE' => 'DEAL',
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
	CCrmEvent::RegisterViewEvent(CCrmOwnerType::Deal, $arParams['ELEMENT_ID'], $currentUserID);
}


$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');
?>
