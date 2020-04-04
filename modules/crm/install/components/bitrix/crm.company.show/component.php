<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

use Bitrix\Crm\Settings\CompanySettings;
use Bitrix\Crm\Format\CompanyAddressFormatter;
use Bitrix\Crm\Format\AddressSeparator;
use Bitrix\Crm\EntityAddress;

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @global CUserTypeManager $USER_FIELD_MANAGER
 * @global CUser $USER
 */
global $USER_FIELD_MANAGER;

CUtil::InitJSCore(array('ajax', 'tooltip'));
$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
if ($arResult['ELEMENT_ID'] <= 0 || !CCrmCompany::CheckReadPermission($arResult['ELEMENT_ID'], $userPermissions))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['MYCOMPANY_MODE'] = (isset($arParams['MYCOMPANY_MODE']) && $arParams['MYCOMPANY_MODE'] === 'Y') ? 'Y' : 'N';
$isMyCompanyMode = ($arResult['MYCOMPANY_MODE'] === 'Y');
$arResult['MYCOMPANY_MODE'] = $isMyCompanyMode ? 'Y' : 'N';

$arResult['EDITABLE_FIELDS'] = array();
$arResult['CAN_EDIT'] = CCrmCompany::CheckUpdatePermission($arResult['ELEMENT_ID'], $userPermissions);
$arResult['TACTILE_FORM_ID'] = $isMyCompanyMode ? 'CRM_MYCOMPANY_EDIT_V12' : 'CRM_COMPANY_EDIT_V12';

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arResult['PATH_TO_COMPANY_SHOW'] = $arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_REQUISITE_EDIT'] = CrmCheckPath('PATH_TO_REQUISITE_EDIT', $arParams['PATH_TO_REQUISITE_EDIT'], $APPLICATION->GetCurPage().'?id=#id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$enableOutmodedFields = CompanySettings::getCurrent()->areOutmodedRequisitesEnabled();
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);

$bEdit = false;
$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];

$obFields = CCrmCompany::GetListEx(
	array(),
	array(
		'ID' => $arParams['ELEMENT_ID']
	)
);
$arFields = $obFields->GetNext();
if(!is_array($arFields))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST'], array()));
}

$arFields['FM'] = array();
$dbResMultiFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arResult['ELEMENT_ID'])
);
while($arMultiFields = $dbResMultiFields->Fetch())
{
	$arFields['FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
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

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$arResult['FORM_ID'] = $isMyCompanyMode ? 'CRM_MYCOMPANY_SHOW_V12' : 'CRM_COMPANY_SHOW_V12';
$arResult['GRID_ID'] = $isMyCompanyMode ? 'CRM_MYCOMPANY_LIST_V12' : 'CRM_COMPANY_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_COMPANY_LIST'];
$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusListEx('EMPLOYEES');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusListEx('INDUSTRY');

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];
$readOnlyMode = !$enableInstantEdit;

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_company_info',
	'name' => GetMessage('CRM_SECTION_COMPANY_INFO'),
	'type' => 'section',
	'isTactile' => true
);

// TITLE -->
// TITLE is displayed in sidebar. The field is added for COMPATIBILITY ONLY
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

// LOGO -->
if(!isset($arResult['ELEMENT']['~LOGO']))
{
	$arResult['LOGO_HTML']  = '';
}
else
{
	$arResult['LOGO_HTML'] = CFile::ShowImage($arResult['ELEMENT']['~LOGO'], 300, 300, 'border=0');
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LOGO',
	'name' => GetMessage('CRM_FIELD_LOGO'),
	'params' => array(),
	'type' => 'custom',
	'value' => $arResult['LOGO_HTML'],
	'isTactile' => true
);
// <-- LOGO

// COMPANY_TYPE -->
// COMPANY_TYPE is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'COMPANY_TYPE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_TYPE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
	'type' => 'label',
	'value' => $arResult['COMPANY_TYPE_LIST'][$arResult['ELEMENT']['~COMPANY_TYPE']],
	'isTactile' => true
);
// <-- COMPANY_TYPE
// INDUSTRY -->
// INDUSTRY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'INDUSTRY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'INDUSTRY',
	'name' => GetMessage('CRM_FIELD_INDUSTRY'),
	'type' => 'label',
	'value' => $arResult['INDUSTRY_LIST'][$arResult['ELEMENT']['~INDUSTRY']],
	'isTactile' => true
);
// <-- INDUSTRY

// EMPLOYEES -->
// EMPLOYEES is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'EMPLOYEES';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMPLOYEES',
	'name' => GetMessage('CRM_FIELD_EMPLOYEES'),
	'type' => 'label',
	'value' => $arResult['EMPLOYEES_LIST'][$arResult['ELEMENT']['~EMPLOYEES']],
	'isTactile' => true
);
// <-- EMPLOYEES

// REVENUE -->
// REVENUE is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'REVENUE';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REVENUE',
	'name' => GetMessage('CRM_FIELD_REVENUE'),
	'value' => isset($arResult['ELEMENT']['REVENUE']) ? CCrmCurrency::MoneyToString($arResult['ELEMENT']['REVENUE'], $currencyID, '#') : '',
	'type' => 'custom',
	'isTactile' => true
);
// <-- REVENUE

// CURRENCY -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'type' => 'label',
	'value' => CCrmCurrency::GetCurrencyName($currencyID),
	'isTactile' => true
);
// <-- CURRENCY

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

// IS_MY_COMPANY -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'IS_MY_COMPANY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IS_MY_COMPANY',
	'name' => GetMessage('CRM_FIELD_IS_MY_COMPANY'),
	'type' => 'label',
	'params' => array(),
	'value' => $arResult['ELEMENT']['IS_MY_COMPANY'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
	'isTactile' => true,
	'visible' => false
);
// <-- IS_MY_COMPANY

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_list',
	'name' => GetMessage('CRM_SECTION_CONTACTS'),
	'type' => 'section',
	'isTactile' => true
);

if (CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CONTACT_ID',
		'name' => GetMessage('CRM_FIELD_COMPANY_CONTACTS'),
		'type' => 'crm_multiple_client_selector',
		'componentParams' => array(
			'OWNER_TYPE_NAME' => CCrmOwnerType::CompanyName,
			'OWNER_ID' => $arResult['ELEMENT_ID'],
			'CONTEXT' => "COMPANY_{$arResult['ELEMENT_ID']}",
			'READ_ONLY' => true,
			'ENTITY_TYPE' => CCrmOwnerType::ContactName,
			'ENTITY_IDS' => \Bitrix\Crm\Binding\ContactCompanyTable::getCompanyContactIDs($arResult['ELEMENT_ID']),
			'ENTITIES_INPUT_NAME' => 'CONTACT_ID',
			'ENABLE_REQUISITES'=> false,
			'ENABLE_LAZY_LOAD'=> true,
			'LOADER' => array(
				'URL' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get()
			),
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		),
		'isTactile' => true
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO'),
	'type' => 'section',
	'isTactile' => true
);

// EMAIL -->
$arMutliFieldTypeInfos = CCrmFieldMulti::GetEntityTypes();
$prefix = strtolower($arResult['FORM_ID']);
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
				'ENTITY_TYPE' => 'CRM_'.CCrmOwnerType::CompanyName,
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

if($enableOutmodedFields)
{
	//region ADDRESS
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDRESS',
		'name' => GetMessage('CRM_FIELD_ADDRESS'),
		'type' => 'custom',
		'value' =>  CompanyAddressFormatter::format(
			$arResult['ELEMENT'],
			array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'TYPE_ID' => EntityAddress::Primary, 'NL2BR' => true)
		),
		'isTactile' => true
	);
	//endregion

	//region ADDRESS_LEGAL
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDRESS_LEGAL',
		'name' => GetMessage('CRM_FIELD_ADDRESS_LEGAL'),
		'type' => 'custom',
		'value' =>  CompanyAddressFormatter::format(
			$arResult['ELEMENT'],
			array('SEPARATOR' => AddressSeparator::HtmlLineBreak, 'TYPE_ID' => EntityAddress::Registered, 'NL2BR' => true)
		),
		'isTactile' => true
	);
	//endregion
}

// BANKING_DETAILS -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BANKING_DETAILS',
	'name' => GetMessage('CRM_FIELD_BANKING_DETAILS'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['BANKING_DETAILS']) ? nl2br($arResult['ELEMENT']['BANKING_DETAILS']) : '',
	'isTactile' => true
);
//<-- BANKING_DETAILS

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
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

if ($arResult['ELEMENT']['DATE_CREATE'] != $arResult['ELEMENT']['DATE_MODIFY'])
{
	// MODIFY_BY -->
	// MODIFY_BY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
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
	// <-- MODIFY_BY

	// DATE_MODIFY -->
	// DATE_MODIFY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_MODIFY',
		'name' => GetMessage('CRM_FIELD_DATE_MODIFY'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['DATE_MODIFY']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_MODIFY']), (time() + CTimeZone::GetOffset())) : ''
	);
	// <-- DATE_MODIFY
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
			"/bitrix/components/bitrix/crm.company.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		'IS_TACTILE' => true
	)
);

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'section_details',
	'name' => GetMessage('CRM_SECTION_DETAILS'),
	'type' => 'section'
);

if(!$isMyCompanyMode && CModule::IncludeModule('lists'))
{
	$arResult['LIST_IBLOCK'] = CLists::getIblockAttachedCrm('COMPANY');
	foreach($arResult['LIST_IBLOCK'] as $iblockId => $iblockName)
	{
		$arResult['LISTS'] = true;
		$arResult['FIELDS']['tab_lists_'.$iblockId][] = array(
			'id' => 'COMPANY_LISTS_'.$iblockId,
			'name' => $iblockName,
			'colspan' => true,
			'type' => 'crm_lists_element',
			'componentData' => array(
				'template' => '',
				'enableLazyLoad' => true,
				'params' => array(
					'ENTITY_ID' => $arResult['ELEMENT']['ID'],
					'ENTITY_TYPE' => CCrmOwnerType::Company,
					'FORM_ID' => $arResult['FORM_ID'],
					'TAB_ID' => 'tab_lists_'.$iblockId,
					'IBLOCK_ID' => $iblockId
				)
			)
		);
	}
}

if (!$isMyCompanyMode && IsModuleInstalled('bizproc') && CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled())
{
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
				'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
					array(
						'company_id' => $arResult['ELEMENT']['ID']
					)
				),
				'SET_TITLE' => 'Y',
				'SET_NAV_CHAIN' => 'Y',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'COMPANY_BIZPROC',
			'name' => GetMessage('CRM_FIELD_COMPANY_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	elseif (isset($_REQUEST['bizproc_log']) && strlen($_REQUEST['bizproc_log']) > 0)
	{
		ob_start();
		$APPLICATION->IncludeComponent('bitrix:bizproc.log',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentCompany',
				'DOCUMENT_TYPE' => 'COMPANY',
				'COMPONENT_VERSION' => 2,
				'DOCUMENT_ID' => 'COMPANY_'.$arResult['ELEMENT']['ID'],
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
			'id' => 'COMPANY_BIZPROC',
			'name' => GetMessage('CRM_FIELD_COMPANY_BIZPROC'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	elseif (isset($_REQUEST['bizproc_start']) && strlen($_REQUEST['bizproc_start']) > 0)
	{
		ob_start();
		$APPLICATION->IncludeComponent('bitrix:bizproc.workflow.start',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentCompany',
				'DOCUMENT_TYPE' => 'COMPANY',
				'DOCUMENT_ID' => 'COMPANY_'.$arResult['ELEMENT']['ID'],
				'TEMPLATE_ID' => $_REQUEST['workflow_template_id'],
				'SET_TITLE'	=>	'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
		$sVal = ob_get_contents();
		ob_end_clean();
		$arResult['FIELDS']['tab_bizproc'][] = array(
			'id' => 'COMPANY_BIZPROC',
			'name' => GetMessage('CRM_FIELD_COMPANY_BIZPROC'),
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
				'id' => 'COMPANY_BIZPROC',
				'name' => GetMessage('CRM_FIELD_COMPANY_BIZPROC'),
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
					'ENTITY' => 'CCrmDocumentCompany',
					'DOCUMENT_TYPE' => 'COMPANY',
					'DOCUMENT_ID' => 'COMPANY_'.$arResult['ELEMENT']['ID'],
					'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
						array(
							'company_id' => $arResult['ELEMENT']['ID'],
							$formTabKey => 'tab_bizproc'
						)),
						array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
							array('company_id' => $arResult['ELEMENT']['ID'])
						),
						array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
					),
					'WORKFLOW_START_URL' => CHTTP::urlAddParams(
						CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
							array('company_id' => $arResult['ELEMENT']['ID'])
						),
						array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
					),
					'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
						array(
							'company_id' => $arResult['ELEMENT']['ID']
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
				'id' => 'COMPANY_BIZPROC',
				'name' => GetMessage('CRM_FIELD_COMPANY_BIZPROC'),
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
		}
	}
}


if (!$isMyCompanyMode && CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$arResult['FIELDS']['tab_contact'][] = array(
		'id' => 'COMPANY_CONTACTS',
		'name' => GetMessage('CRM_FIELD_COMPANY_CONTACTS'),
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
				'INTERNAL_FILTER' => array('ASSOCIATED_COMPANY_ID' => $arResult['ELEMENT']['ID']),
				'INTERNAL_CONTEXT' => array('COMPANY_ID' => $arResult['ELEMENT']['ID']),
				'GRID_ID_SUFFIX' => 'COMPANY_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_contact',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => true,
				'PRESERVE_HISTORY' => true
			)
		)
	);
}
if (!$isMyCompanyMode && CCrmDeal::CheckReadPermission(0, $userPermissions))
{
	$arResult['FIELDS']['tab_deal'][] = array(
		'id' => 'COMPANY_DEAL',
		'name' => GetMessage('CRM_FIELD_COMPANY_DEAL'),
		'colspan' => true,
		'type' => 'crm_deal_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'DEAL_COUNT' => '20',
				'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'],
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'INTERNAL_FILTER' => array('COMPANY_ID' => $arResult['ELEMENT']['ID']),
				'INTERNAL_CONTEXT' => array('COMPANY_ID' => $arResult['ELEMENT']['ID']),
				'GRID_ID_SUFFIX' => 'COMPANY_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_deal',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => true,
				'PRESERVE_HISTORY' => true
			)
		)
	);
}
$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'section_event',
	'name' => GetMessage('CRM_SECTION_EVENT'),
	'type' => 'section'
);
if (!$isMyCompanyMode && CCrmQuote::CheckReadPermission(0, $userPermissions))
{
	$arResult['FIELDS']['tab_quote'][] = array(
		'id' => 'DEAL_QUOTE',
		'name' => GetMessage('CRM_FIELD_COMPANY_QUOTE'),
		'colspan' => true,
		'type' => 'crm_quote_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'QUOTE_COUNT' => '20',
				'PATH_TO_QUOTE_SHOW' => $arResult['PATH_TO_QUOTE_SHOW'],
				'PATH_TO_QUOTE_EDIT' => $arResult['PATH_TO_QUOTE_EDIT'],
				'INTERNAL_FILTER' => array('COMPANY_ID' => $arResult['ELEMENT']['ID']),
				'INTERNAL_CONTEXT' => array('COMPANY_ID' => $arResult['ELEMENT']['ID']),
				'GRID_ID_SUFFIX' => 'COMPANY_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_quote',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => true,
				'PRESERVE_HISTORY' => true
			)
		)
	);
}

if (!$isMyCompanyMode && CCrmInvoice::CheckReadPermission(0, $userPermissions))
{
	$arResult['FIELDS']['tab_invoice'][] = array(
		'id' => 'COMPANY_INVOICE',
		'name' => GetMessage('CRM_FIELD_COMPANY_INVOICE'),
		'colspan' => true,
		'type' => 'crm_invoice_list',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'params' => array(
				'INVOICE_COUNT' => '20',
				'PATH_TO_INVOICE_SHOW' => $arParams['PATH_TO_INVOICE_SHOW'],
				'PATH_TO_INVOICE_EDIT' => $arParams['PATH_TO_INVOICE_EDIT'],
				'PATH_TO_INVOICE_PAYMENT' => $arParams['PATH_TO_INVOICE_PAYMENT'],
				'INTERNAL_FILTER' => array('UF_COMPANY_ID' => $arResult['ELEMENT']['ID']),
				'SUM_PAID_CURRENCY' => (isset($arResult['ELEMENT']['CURRENCY_ID'])) ? $arResult['ELEMENT']['CURRENCY_ID'] : '',
				'GRID_ID_SUFFIX' => 'COMPANY_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_invoice',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'ENABLE_TOOLBAR' => 'Y',
				'PRESERVE_HISTORY' => true,
				'INTERNAL_ADD_BTN_TITLE' => GetMessage('CRM_COMPANY_ADD_INVOICE_TITLE')
			)
		)
	);
}

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'COMPANY_EVENT',
	'name' => GetMessage('CRM_FIELD_COMPANY_EVENT'),
	'colspan' => true,
	'type' => 'crm_event_view',
	'componentData' => array(
		'template' => '',
		'enableLazyLoad' => true,
		'contextId' => "COMPANY_{$arResult['ELEMENT']['ID']}_EVENT",
		'params' => array(
			'AJAX_OPTION_ADDITIONAL' => "COMPANY_{$arResult['ELEMENT']['ID']}_EVENT",
			'ENTITY_TYPE' => 'COMPANY',
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

if (!$isMyCompanyMode)
{
	$arResult['TREE_CONTAINER_ID'] = $arResult['FORM_ID'].'_tree_wrapper';
	$arResult['TAB_TREE_OPEN'] = isset($_REQUEST['active_tab']) && $_REQUEST['active_tab'] == 'tab_tree';
	$arResult['FIELDS']['tab_tree'] = array(array(
		'id' => 'ENTITY_TREE',
		'name' => GetMessage('CRM_FIELD_ENTITY_TREE'),
		'colspan' => true,
		'type' => 'custom',
		'value' => '<div id="'.htmlspecialcharsbx($arResult['TREE_CONTAINER_ID']).'"></div>'
	));
}

if (!$isMyCompanyMode)
{
	$arResult['FIELDS']['tab_event'][] = array(
		'id' => 'section_event_contact',
		'name' => GetMessage('CRM_SECTION_EVENT_CONTACT'),
		'type' => 'section'
	);

	$arResult['FIELDS']['tab_event'][] = array(
		'id' => 'COMPANY_CONTACT_EVENT',
		'name' => GetMessage('CRM_FIELD_COMPANY_EVENT_CONTACT'),
		'colspan' => true,
		'type' => 'crm_event_view',
		'componentData' => array(
			'template' => '',
			'enableLazyLoad' => true,
			'contextId' => "COMPANY_{$arResult['ELEMENT']['ID']}_CONTACT_EVENT",
			'params' => array(
				'AJAX_OPTION_ADDITIONAL' => "COMPANY_{$arResult['ELEMENT']['ID']}_CONTACT_EVENT",
				'OWNER_TYPE' => 'COMPANY',
				'OWNER_ID' => $arResult['ELEMENT']['ID'],
				'ENTITY_TYPE' => 'CONTACT',
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_event',
				'VIEW_ID' => 'contact',
				'INTERNAL' => 'Y',
				'SHOW_INTERNAL_FILTER' => 'Y',
				'PRESERVE_HISTORY' => true,
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			)
		)
	);
}

// LIVE FEED SECTION -->
if (!$isMyCompanyMode)
{
	$arResult['FIELDS']['tab_live_feed'][] = array(
		'id' => 'section_live_feed',
		'name' => GetMessage('CRM_SECTION_LIVE_FEED'),
		'type' => 'section'
	);

	$liveFeedHtml = '';
	if ($arParams['ELEMENT_ID'] > 0)
	{
		if (CCrmLiveFeedComponent::needToProcessRequest($_SERVER['REQUEST_METHOD'], $_REQUEST))
		{
			ob_start();
			$APPLICATION->IncludeComponent('bitrix:crm.entity.livefeed',
				'',
				array(
					'DATE_TIME_FORMAT' => (LANGUAGE_ID == 'en' ? "j F Y g:i a" : (LANGUAGE_ID == 'de' ? "j. F Y, G:i" : "j F Y G:i")),
					'CAN_EDIT' => $arResult['CAN_EDIT'],
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $arParams['ELEMENT_ID'],
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
}
// <-- LIVE FEED SECTION

if (!$isMyCompanyMode)
{
	$arResult['FIELDS']['tab_activity'][] = array(
		'id' => 'section_activity_grid',
		'name' => GetMessage('CRM_SECTION_ACTIVITY_GRID'),
		'type' => 'section'
	);

	$activityBindings = array(array('TYPE_NAME' => CCrmOwnerType::CompanyName, 'ID' => $arParams['ELEMENT_ID']));
	foreach (CCrmLead::GetAssociatedIDs(CCrmOwnerType::Company, $arParams['ELEMENT_ID']) as $leadID)
	{
		$activityBindings[] = array('TYPE_NAME' => CCrmOwnerType::LeadName, 'ID' => $leadID);
	}
	$arResult['FIELDS']['tab_activity'][] = array(
		'id' => 'COMPANY_ACTIVITY_GRID',
		'name' => GetMessage('CRM_FIELD_COMPANY_ACTIVITY'),
		'colspan' => true,
		'type' => 'crm_activity_list',
		'componentData' => array(
			'template' => 'grid',
			'enableLazyLoad' => true,
			'params' => array(
				'BINDINGS' => $activityBindings,
				'PREFIX' => 'COMPANY_ACTIONS_GRID',
				'PERMISSION_TYPE' => 'WRITE',
				'FORM_TYPE' => 'show',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_activity',
				'USE_QUICK_FILTER' => 'Y',
				'PRESERVE_HISTORY' => true
			)
		)
	);
}

$arResult['FIELDS']['tab_requisite'][] = array(
	'id' => 'COMPANY_REQUISITE',
	'name' => GetMessage('CRM_FIELD_COMPANY_REQUISITE'),
	'colspan' => true,
	'type' => 'crm_requisite_list',
	'componentData' => array(
		'template' => '',
		'enableLazyLoad' => true,
		'params' => array(
			'REQUISITE_COUNT' => '20',
			'PATH_TO_REQUISITE_EDIT' => $arParams['PATH_TO_REQUISITE_EDIT'],
			'INTERNAL_FILTER' => array('=ENTITY_TYPE_ID' => CCrmOwnerType::Company, '=ENTITY_ID' => $arResult['ELEMENT']['ID']),
			'INTERNAL_CONTEXT' => array('COMPANY_ID' => $arResult['ELEMENT']['ID']),
			'GRID_ID_SUFFIX' => 'COMPANY_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_requisite',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'ENABLE_TOOLBAR' => true,
			'PRESERVE_HISTORY' => true
		)
	)
);

if(!isset($_REQUEST['bxajaxid']) && \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled())
{
	CCrmEvent::RegisterViewEvent(CCrmOwnerType::Company, $arParams['ELEMENT_ID'], $currentUserID);
}

$arResult['ACTION_URI'] = $arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;
$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');

?>
