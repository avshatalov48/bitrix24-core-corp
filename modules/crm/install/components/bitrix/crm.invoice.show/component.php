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

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

$CCrmInvoice = new CCrmInvoice();
if ($CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

CUtil::InitJSCore(array('ajax'));
Bitrix\Main\UI\Extension::load("ui.tooltip");

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();
$arResult['CAN_EDIT'] = !$CCrmInvoice->cPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'WRITE');
$arResult['EDITABLE_FIELDS'] = array(
	'ORDER_TOPIC',
	'RESPONSIBLE_ID',
	'STATUS_ID',
	'DATE_PAY_BEFORE'
);
$arResult['TACTILE_FORM_ID'] = 'CRM_INVOICE_EDIT_V12';

$arParams['PATH_TO_INVOICE_LIST'] = CrmCheckPath('PATH_TO_INVOICE_LIST', $arParams['PATH_TO_INVOICE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_INVOICE_SHOW'] = CrmCheckPath('PATH_TO_INVOICE_SHOW', $arParams['PATH_TO_INVOICE_SHOW'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&show');
$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_QUOTE_SHOW'] = CrmCheckPath('PATH_TO_QUOTE_SHOW', $arParams['PATH_TO_QUOTE_SHOW'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&show');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

global $USER_FIELD_MANAGER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmInvoice::$sUFEntityID);

$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];

$arFields = CCrmInvoice::GetByID($arParams['ELEMENT_ID']);

$fullNameFormat = $arParams['NAME_TEMPLATE'];

$arFields['RESPONSIBLE_FORMATTED_NAME'] = intval($arFields['RESPONSIBLE_ID']) > 0
	? CUser::FormatName(
		$fullNameFormat,
		array(
			'LOGIN' => $arFields['RESPONSIBLE_LOGIN'],
			'NAME' => $arFields['RESPONSIBLE_NAME'],
			'LAST_NAME' => $arFields['RESPONSIBLE_LAST_NAME'],
			'SECOND_NAME' => $arFields['RESPONSIBLE_SECOND_NAME']
		),
		true, false
	) : GetMessage('RESPONSIBLE_NOT_ASSIGNED');


$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['STATUS_LIST'] = array();
$statusList = CCrmStatus::GetStatusList('INVOICE_STATUS');
foreach ($statusList as $sStatusId => $sStatusTitle)
{
	if ($CCrmInvoice->cPerms->GetPermType('INVOICE', $bEdit ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}

$arFields['STATUS_TEXT'] = '';
if (isset($arFields['STATUS_ID']) && $arFields['STATUS_ID'] !== '')
{
	$arFields['STATUS_TEXT'] = isset($arFields['STATUS_ID'])
		&& isset($arResult['STATUS_LIST'][$arFields['STATUS_ID']])
		? $arResult['STATUS_LIST'][$arFields['STATUS_ID']] : '';
}

$arResult['ELEMENT'] = $arFields;
if ($arParams['IS_RECURRING'] === "Y")
{
	if ($arFields['IS_RECURRING'] !== "Y")
	{
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_RECUR'], array()));
	}

	$recurData = Bitrix\Crm\InvoiceRecurTable::getList(
		array(
			"filter" => array("=INVOICE_ID" => $arParams['ELEMENT_ID'])
		)
	);
	$arResult['RECURRING_DATA'] = $recurData->fetch();
}
elseif ($arFields['IS_RECURRING'] === "Y")
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_LIST'], array()));
}
	
unset($arFields);

if (empty($arResult['ELEMENT']['ID']))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_INVOICE_LIST'], array()));
}

$bTaxMode = CCrmTax::isTaxMode();
$bStatusSuccess = false;
$bStatusFailed = false;
if (isset($arResult['ELEMENT']['STATUS_ID']) && $arResult['ELEMENT']['STATUS_ID'] !== '')
{
	$bStatusSuccess = CCrmStatusInvoice::isStatusSuccess($arResult['ELEMENT']['STATUS_ID']);
	if ($bStatusSuccess)
		$bStatusFailed = false;
	else
		$bStatusFailed = CCrmStatusInvoice::isStatusFailed($arResult['ELEMENT']['STATUS_ID']);
}
$arResult['STATUS_SUCCESS'] = $arResult['ELEMENT']['STATUS_SUCCESS'] = $bStatusSuccess ? 'Y' : 'N';
$arResult['STATUS_FAILED'] = $arResult['ELEMENT']['STATUS_FAILED'] = $bStatusFailed ? 'Y' : 'N';
$arResult['ELEMENT']['REASON_MARKED_SUCCESS'] = $bStatusSuccess ? $arResult['ELEMENT']['REASON_MARKED'] : '';
if(!$bStatusFailed)
{
	$arResult['ELEMENT']['REASON_MARKED'] = '';
}

$currencyID = isset($arResult['ELEMENT']['CURRENCY'])
	? $arResult['ELEMENT']['CURRENCY'] : CCrmInvoice::GetCurrencyID();

$dealID = isset($arResult['ELEMENT']['UF_DEAL_ID']) ? $arResult['ELEMENT']['UF_DEAL_ID'] : 0;
$arResult['PATH_TO_DEAL_SHOW'] = $arResult['ELEMENT']['UF_DEAL_SHOW_URL'] = $dealID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $dealID))
	: '';

if ($dealID > 0)
{
	$arResult['ELEMENT']['UF_DEAL_TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Deal, $dealID, false);
}

$quoteID = isset($arResult['ELEMENT']['UF_QUOTE_ID']) ? $arResult['ELEMENT']['UF_QUOTE_ID'] : 0;
$arResult['PATH_TO_QUOTE_SHOW'] = $arResult['ELEMENT']['UF_QUOTE_SHOW_URL'] = $quoteID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_QUOTE_SHOW'], array('quote_id' => $quoteID))
	: '';

if ($quoteID > 0)
{
	$arResult['ELEMENT']['UF_QUOTE_TITLE'] = CCrmOwnerType::GetCaption(CCrmOwnerType::Quote, $quoteID, false);
}

$companyID = isset($arResult['ELEMENT']['UF_COMPANY_ID']) ? $arResult['ELEMENT']['UF_COMPANY_ID'] : 0;
$arResult['PATH_TO_COMPANY_SHOW'] = $arResult['ELEMENT']['UF_COMPANY_SHOW_URL'] = $companyID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $companyID))
	: '';
if ($companyID > 0)
{
	$dbResult = CCrmCompany::GetListEx(
		array(),
		array(
			'=ID' => $companyID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'TITLE', 'LOGO')
	);

	$entityInfo = $dbResult ? $dbResult->Fetch() : null;
	if(is_array($entityInfo))
	{
		$arResult['ELEMENT']['UF_COMPANY_TITLE'] = isset($entityInfo['TITLE']) ? $entityInfo['TITLE'] : '';
		$arResult['ELEMENT']['UF_COMPANY_LOGO'] = isset($entityInfo['LOGO']) ? $entityInfo['LOGO'] : 0;
	}

	$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyID, 'TYPE_ID' => 'EMAIL')
	);

	while($field = $dbResult->Fetch())
	{
		if($field['VALUE'])
		{
			$arResult['ELEMENT']['UF_COMPANY_EMAIL'] = $field['VALUE'];
			break;
		}
	}
}

$contactID = isset($arResult['ELEMENT']['UF_CONTACT_ID']) ? $arResult['ELEMENT']['UF_CONTACT_ID'] : 0;
$arResult['PATH_TO_CONTACT_SHOW'] = $arResult['ELEMENT']['UF_CONTACT_SHOW_URL'] = $contactID > 0
	? CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $contactID))
	: '';
if ($contactID > 0)
{
	$dbResult = CCrmContact::GetListEx(
		array(),
		array('=ID' => $contactID, 'CHECK_PERMISSIONS' => 'N'),
		false,
		false,
		array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO')
	);

	$entityInfo = $dbResult ? $dbResult->Fetch() : null;
	if(is_array($entityInfo))
	{
		$arResult['ELEMENT']['UF_CONTACT_FORMATTED_NAME'] = CCrmContact::PrepareFormattedName($entityInfo);
		$arResult['ELEMENT']['UF_CONTACT_PHOTO'] = isset($entityInfo['PHOTO']) ? $entityInfo['PHOTO'] : 0;
		$arResult['ELEMENT']['UF_CONTACT_POST'] = isset($entityInfo['POST']) ? $entityInfo['POST'] : '';
	}

	$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactID, 'TYPE_ID' => 'EMAIL')
	);

	while($field = $dbResult->Fetch())
	{
		if($field['VALUE'])
		{
			$arResult['ELEMENT']['UF_CONTACT_EMAIL'] = $field['VALUE'];
			break;
		}
	}
}

$personTypeID = 0;
if ($companyID > 0 || $contactID > 0)
{
	// Determine person type
	$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
	if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
	{
		if ($companyID > 0)
		{
			$personTypeID = $arPersonTypes['COMPANY'];
		}
		elseif ($contactID > 0)
		{
			$personTypeID = $arPersonTypes['CONTACT'];
		}
	}

	// Get location from properties
	if ($bTaxMode && $arResult['ELEMENT_ID'] > 0 && !isset($arResult['ELEMENT']['PR_LOCATION']))
	{
		$tmpArProps = CCrmInvoice::GetProperties($arResult['ELEMENT_ID'], $personTypeID);
		if ($tmpArProps !== false)
		{
			if (isset($tmpArProps['PR_LOCATION']))
			{
				$arResult['ELEMENT']['PR_LOCATION'] = $tmpArProps['PR_LOCATION']['VALUE'];
			}
		}
		unset($tmpArProps);
	}
}

$arResult['PAY_SYSTEM_LIST'] = $personTypeID > 0 ? CCrmPaySystem::GetPaySystemsListItems($personTypeID, true) : array();
if (isset($arResult['ELEMENT']['PAY_SYSTEM_ID']))
{
	if (array_key_exists($arResult['ELEMENT']['PAY_SYSTEM_ID'], $arResult['PAY_SYSTEM_LIST']))
	{
		$arResult['ELEMENT']['PAY_SYSTEM_NAME'] = $arResult['PAY_SYSTEM_LIST'][$arResult['ELEMENT']['PAY_SYSTEM_ID']];
	}
	else
	{
		$data = \Bitrix\Sale\PaySystem\Manager::getById($arResult['ELEMENT']['PAY_SYSTEM_ID']);
		if ($data)
			$arResult['ELEMENT']['PAY_SYSTEM_NAME'] = $data['NAME'];
	}
}

$isExternal = $arResult['IS_EXTERNAL'] = isset($arResult['ELEMENT']['ORIGINATOR_ID']) && isset($arResult['ELEMENT']['ORIGIN_ID']) && intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0;

$arResult['ERROR_MESSAGE'] = '';

$arResult['FORM_ID'] = 'CRM_INVOICE_SHOW_V12'.($isExternal ? "_E" : "");
$arResult['GRID_ID'] = 'CRM_INVOICE_LIST_V12'.($isExternal ? "_E" : "");
$arResult['BACK_URL'] = $arParams['PATH_TO_INVOICE_LIST'];

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];
$arResult['FIELDS'] = array();

$readOnlyMode = !$enableInstantEdit || $isExternal;

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_info',
	'name' => GetMessage('CRM_SECTION_INVOICE_INFO'),
	'type' => 'section',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACCOUNT_NUMBER',
	'name' => GetMessage('CRM_FIELD_ACCOUNT_NUMBER'),
	'params' => array('size' => 100),
	'value' => isset($arResult['ELEMENT']['ACCOUNT_NUMBER']) ? $arResult['ELEMENT']['ACCOUNT_NUMBER'] : '',
	'type' => 'label',
	'required' => true,
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ORDER_TOPIC',
	'name' => GetMessage('CRM_FIELD_ORDER_TOPIC'),
	'params' => array('size' => 255),
	'value' => isset($arResult['ELEMENT']['ORDER_TOPIC']) ? $arResult['ELEMENT']['ORDER_TOPIC'] : '',
	'type' => 'label',
	'required' => true,
	'isTactile' => true
);
if ($arParams['IS_RECURRING'] !== "Y")
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'STATUS_ID',
		'name' => GetMessage('CRM_FIELD_STATUS_ID'),
		'type' => 'label',
		'value' => $arResult['ELEMENT']['STATUS_TEXT'],
		'required' => true,
		'isTactile' => true
	);
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PAY_VOUCHER_DATE',
	'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_DATE'),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['PAY_VOUCHER_DATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['PAY_VOUCHER_DATE']), 'SHORT', SITE_ID)) : '',
	'visible' => $bStatusSuccess,
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PAY_VOUCHER_NUM',
	'name' => GetMessage('CRM_FIELD_PAY_VOUCHER_NUM'),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['PAY_VOUCHER_NUM']) ? $arResult['ELEMENT']['PAY_VOUCHER_NUM'] : '',
	'visible' => $bStatusSuccess,
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REASON_MARKED_SUCCESS',
	'name' => GetMessage('CRM_FIELD_REASON_MARKED_SUCCESS'),
	'value' => isset($arResult['ELEMENT']['REASON_MARKED_SUCCESS']) ? $arResult['ELEMENT']['REASON_MARKED_SUCCESS'] : '',
	'type' => 'label',
	'visible' => $bStatusSuccess,
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_MARKED',
	'name' => GetMessage('CRM_FIELD_DATE_MARKED'),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['DATE_MARKED']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_MARKED']), 'SHORT', SITE_ID)) : '',
	'visible' => $bStatusFailed,
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REASON_MARKED',
	'name' => GetMessage('CRM_FIELD_REASON_MARKED'),
	'value' => isset($arResult['ELEMENT']['REASON_MARKED']) ? $arResult['ELEMENT']['REASON_MARKED'] : '',
	'type' => 'label',
	'visible' => $bStatusFailed,
	'isTactile' => true
);

if ($arParams['IS_RECURRING'] !== "Y")
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_BILL',
		'name' => GetMessage('CRM_FIELD_DATE_BILL'),
		'type' => 'label',
		'value' => !empty($arResult['ELEMENT']['DATE_BILL']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_BILL']), 'SHORT', SITE_ID)) : '',
		'isTactile' => true
	);

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_PAY_BEFORE',
		'name' => GetMessage('CRM_FIELD_DATE_PAY_BEFORE'),
		'type' => 'label',
		'value' => !empty($arResult['ELEMENT']['DATE_PAY_BEFORE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['DATE_PAY_BEFORE']), 'SHORT', SITE_ID)) : '',
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

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'RESPONSIBLE_ID',
	'name' => GetMessage('CRM_FIELD_RESPONSIBLE_ID'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['RESPONSIBLE_ID'])
		? CCrmViewHelper::PrepareFormResponsible($arResult['ELEMENT']['RESPONSIBLE_ID'], $arParams['NAME_TEMPLATE'], $arParams['PATH_TO_USER_PROFILE'])
		: '',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => htmlspecialcharsbx(isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID),
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'UF_DEAL_ID',
	'name' => GetMessage('CRM_FIELD_UF_DEAL_ID'),
	'value' => isset($arResult['ELEMENT']['UF_DEAL_TITLE'])
		? (!CCrmDeal::CheckReadPermission($dealID)
			? htmlspecialcharsbx($arResult['ELEMENT']['UF_DEAL_TITLE']) :
			'<a href="'.$arResult['PATH_TO_DEAL_SHOW'].'" bx-tooltip-user-id="DEAL_'.$dealID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.deal.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_deal">'.htmlspecialcharsbx($arResult['ELEMENT']['UF_DEAL_TITLE']).'</a>'
		) : GetMessage('CRM_INVOICE_DEAL_NOT_ASSIGNED'),
	'type' => 'custom',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'UF_QUOTE_ID',
	'name' => GetMessage('CRM_FIELD_UF_QUOTE_ID'),
	'value' => isset($arResult['ELEMENT']['UF_QUOTE_TITLE'])
		? (!CCrmQuote::CheckReadPermission($quoteID)
			? htmlspecialcharsbx($arResult['ELEMENT']['UF_QUOTE_TITLE']) :
			'<a href="'.$arResult['PATH_TO_QUOTE_SHOW'].'" bx-tooltip-user-id="QUOTE_'.$quoteID.'" bx-tooltip-loader="'.htmlspecialcharsbx('/bitrix/components/bitrix/crm.quote.show/card.ajax.php').'" bx-tooltip-classname="crm_balloon_quote">'.htmlspecialcharsbx($arResult['ELEMENT']['UF_QUOTE_TITLE']).'</a>'
		) : GetMessage('CRM_INVOICE_QUOTE_NOT_ASSIGNED'),
	'type' => 'custom',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_invoice_payer',
	'name' => GetMessage('CRM_SECTION_INVOICE_PAYER'),
	'type' => 'section',
	'isTactile' => true
);

if(CCrmCompany::CheckReadPermission(0, $userPermissions) || CCrmContact::CheckReadPermission(0, $userPermissions))
{
	$companyID = isset($arResult['ELEMENT']['UF_COMPANY_ID']) ? $arResult['ELEMENT']['UF_COMPANY_ID'] : 0;
	$contactID = isset($arResult['ELEMENT']['UF_CONTACT_ID']) ? $arResult['ELEMENT']['UF_CONTACT_ID'] : 0;

	if($companyID > 0 || $contactID > 0)
	{
		$primaryEntityTypeName = CCrmOwnerType::CompanyName;
		$primaryEntityID = $companyID;
	}
	else
	{
		$primaryEntityTypeName = CCrmOwnerType::ContactName;
		$primaryEntityID = $contactID;
	}

	$secondaryIDs = array();
	if($contactID > 0)
	{
		$secondaryIDs[] = $contactID;
	}

	$requisiteIdLinked = 0;
	$bankDetailIdLinked = 0;
	$requisteBindings = Bitrix\Crm\Requisite\EntityLink::getByEntity(CCrmOwnerType::Invoice, $arParams['ELEMENT_ID']);
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

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CLIENT',
		'name' => GetMessage('CRM_FIELD_CLIENT_ID'),
		'type' => 'crm_composite_client_selector',
		'componentParams' => array(
			'CONTEXT' => "INVOICE_{$arParams['ELEMENT_ID']}",
			'OWNER_TYPE' => CCrmOwnerType::DealName,
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'READ_ONLY' => true,
			'PRIMARY_ENTITY_TYPE' => $primaryEntityTypeName,
			'PRIMARY_ENTITY_ID' => $primaryEntityID,
			'SECONDARY_ENTITY_TYPE' => CCrmOwnerType::ContactName,
			'SECONDARY_ENTITY_IDS' => $secondaryIDs,
			'ENABLE_MULTIPLICITY' => false,
			'CUSTOM_MESSAGES' => array(
				'SECONDARY_ENTITY_HEADER' => GetMessage('CRM_INVOICE_SHOW_CONTACT_SELECTOR_HEADER')
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

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LOCATION_ID',
	'name' => GetMessage('CRM_FIELD_LOCATION'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => ($bTaxMode && isset($arResult['ELEMENT']['PR_LOCATION']))
		? CCrmLocations::getLocationStringByCode($arResult['ELEMENT']['PR_LOCATION'])
		: '',
	'isTactile' => true,
	'visible' => $bTaxMode
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_pay_system',
	'name' => GetMessage('CRM_SECTION_PAY_SYSTEM'),
	'type' => 'section',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PAY_SYSTEM_ID',
	'name' => GetMessage('CRM_FIELD_PAY_SYSTEM_ID'),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['PAY_SYSTEM_NAME'])
		? $arResult['ELEMENT']['PAY_SYSTEM_NAME'] : '',
	'isTactile' => true
);

if(CCrmCompany::CheckReadPermission(0, $userPermissions))
{
	$myCompanyId = isset($arResult['ELEMENT']['UF_MYCOMPANY_ID']) ? (int)$arResult['ELEMENT']['UF_MYCOMPANY_ID'] : 0;
	if ($myCompanyId > 0)
	{
		$mcRequisiteIdLinked = 0;
		$mcBankDetailIdLinked = 0;
		$requisteBindings = Bitrix\Crm\Requisite\EntityLink::getByEntity(CCrmOwnerType::Invoice, $arParams['ELEMENT_ID']);
		if(is_array($requisteBindings))
		{
			if(isset($requisteBindings['MC_REQUISITE_ID']))
			{
				$mcRequisiteIdLinked = (int)$requisteBindings['MC_REQUISITE_ID'];
			}

			if(isset($requisteBindings['MC_BANK_DETAIL_ID']))
			{
				$mcBankDetailIdLinked = (int)$requisteBindings['MC_BANK_DETAIL_ID'];
			}
		}
	}

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'UF_MYCOMPANY_ID',
		'name' => GetMessage('CRM_INVOICE_FIELD_UF_MYCOMPANY_ID1'),
		'type' => 'crm_single_client_selector',
		'componentParams' => array(
			'CONTEXT' => "INVOICE_{$arParams['ELEMENT_ID']}",
			'OWNER_TYPE' => CCrmOwnerType::InvoiceName,
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'ENTITY_TYPE' => CCrmOwnerType::CompanyName,
			'ENTITY_ID' => $myCompanyId,
			'REQUISITE_ID' => $mcRequisiteIdLinked,
			'BANK_DETAIL_ID' => $mcBankDetailIdLinked,
			'ENABLE_REQUISITES'=> true,
			'ENTITY_SELECTOR_SEARCH_OPTIONS' => array(
				'ONLY_MY_COMPANIES' => 'Y'
			),
			'READ_ONLY' => true,
			'FORM_NAME' => $arResult['FORM_ID'],
			'NAME_TEMPLATE' => \Bitrix\Crm\Format\PersonNameFormatter::getFormat()
		),
		'isTactile' => true
	);
}


ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.interface.form.recurring',
	'show',
	array(
		'DATA' => $arResult['RECURRING_DATA']['PARAMS'],
		'IS_RECURRING' => $arParams['IS_RECURRING'],
		'PATH_TO_INVOICE_EDIT' => CComponentEngine::makePathFromTemplate($arParams['PATH_TO_INVOICE_EDIT']."#section_recurring", array('invoice_id' => $arParams['ELEMENT_ID']))
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

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_comments',
	'name' => GetMessage('CRM_SECTION_COMMENTS'),
	'type' => 'section',
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['COMMENTS']) ? $arResult['ELEMENT']['COMMENTS'] : '',
	'params' => array(),
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'USER_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_USER_DESCRIPTION'),
	'type' => 'custom',
	'value' => isset($arResult['ELEMENT']['USER_DESCRIPTION']) ? $arResult['ELEMENT']['USER_DESCRIPTION'] : '',
	'params' => array(),
	'isTactile' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$icnt = count($arResult['FIELDS']['tab_1']);

$arResult['USER_FIELD_COUNT'] = $CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	false,
	true,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.invoice.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#",
		'IS_TACTILE' => true
	)
);

if (count($arResult['FIELDS']['tab_1']) == $icnt)
	unset($arResult['FIELDS']['tab_1'][$icnt - 1]);

// PRODUCT ROW SECTION -->
$arResult['FIELDS']['tab_product_rows'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);

$sProductsHtml = '<script type="text/javascript">var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>';

if (intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0)
{
	$sProductsHtml .= '<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_EDIT").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'EDIT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">
	<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_VIEW").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'VIEW\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">
	<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_PRINT").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'PRINT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')"><br /><br />';
}

// Product rows
$arResult['PRODUCT_ROW_EDITOR_ID'] = 'invoice_'.strval($arParams['ELEMENT_ID']).'_product_editor';
if($arParams['ELEMENT_ID'] > 0)
{
	// Determine person type
	$arPersonTypes = CCrmPaySystem::getPersonTypeIDs();
	$personTypeId = 0;
	if (isset($arPersonTypes['COMPANY']) && isset($arPersonTypes['CONTACT']))
	{
		if (intval($arResult['ELEMENT']['UF_COMPANY_ID']) > 0)
			$personTypeId = $arPersonTypes['COMPANY'];
		elseif (intval($arResult['ELEMENT']['UF_CONTACT_ID']) > 0)
			$personTypeId = $arPersonTypes['CONTACT'];
	}

	$componentSettings = array(
		'ID' => $arResult['PRODUCT_ROW_EDITOR_ID'],
		'FORM_ID' => $arResult['FORM_ID'],
		'OWNER_ID' => $arParams['ELEMENT_ID'],
		'OWNER_TYPE' => 'I',
		'PERMISSION_TYPE' => 'READ',
		'HIDE_MODE_BUTTON' => 'Y',
		'CURRENCY_ID' => $arResult['ELEMENT']['CURRENCY'],
		'PERSON_TYPE_ID' => $personTypeId,
		'LOCATION_ID' => $bTaxMode ? $arResult['ELEMENT']['PR_LOCATION'] : '',
		'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
		'TOTAL_SUM' => isset($arResult['ELEMENT']['PRICE']) ? $arResult['ELEMENT']['PRICE'] : null,
		'TOTAL_TAX' => isset($arResult['ELEMENT']['TAX_VALUE']) ? $arResult['ELEMENT']['TAX_VALUE'] : null,
		'PATH_TO_PRODUCT_EDIT' => $arParams['PATH_TO_PRODUCT_EDIT'],
		'PATH_TO_PRODUCT_SHOW' => $arParams['PATH_TO_PRODUCT_SHOW']
	);
	if (is_array($productRowSettings) && count($productRowSettings) > 0)
	{
		if (isset($productRowSettings['ENABLE_DISCOUNT']))
			$componentSettings['ENABLE_DISCOUNT'] = $productRowSettings['ENABLE_DISCOUNT'] ? 'Y' : 'N';
		if (isset($productRowSettings['ENABLE_TAX']))
			$componentSettings['ENABLE_TAX'] = $productRowSettings['ENABLE_TAX'] ? 'Y' : 'N';
	}
	$sProductsHtml = '';
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
		'',
		$componentSettings,
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sProductsHtml .= ob_get_contents();
	ob_end_clean();
	unset($componentSettings);
}

$arResult['FIELDS']['tab_product_rows'][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);
// <-- PRODUCT ROW SECTION


if($arResult['ELEMENT']['UF_CONTACT_ID'] && $arResult['ELEMENT']['UF_CONTACT_EMAIL'] <> '')
{
	$arResult['COMMUNICATION'] = array(
		'entityType' => 'CONTACT',
		'entityId' => $arResult['ELEMENT']['UF_CONTACT_ID'],
		'entityTitle' => $arResult['ELEMENT']['UF_CONTACT_FORMATTED_NAME'],
		'type' => 'EMAIL',
		'value' => $arResult['ELEMENT']['UF_CONTACT_EMAIL']
	);
}
elseif($arResult['ELEMENT']['UF_COMPANY_ID'] && $arResult['ELEMENT']['UF_COMPANY_EMAIL'] <> '')
{
	$arResult['COMMUNICATION'] = array(
		'entityType' => 'COMPANY',
		'entityId' => $arResult['ELEMENT']['UF_COMPANY_ID'],
		'entityTitle' => $arResult['ELEMENT']['UF_COMPANY_TITLE'],
		'type' => 'EMAIL',
		'value' => $arResult['ELEMENT']['UF_COMPANY_EMAIL']
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

$mailTemplateResult = \CCrmMailTemplate::getList(
	array('SORT' => 'ASC', 'ENTITY_TYPE_ID' => 'DESC', 'TITLE'=> 'ASC'),
	array(
		'IS_ACTIVE' => 'Y',
		'__INNER_FILTER_TYPE' => array(
			'LOGIC' => 'OR',
			'__INNER_FILTER_TYPE_1' => array('ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice),
			'__INNER_FILTER_TYPE_2' => array('ENTITY_TYPE_ID' => 0),
		),
		'__INNER_FILTER_SCOPE' => array(
			'LOGIC' => 'OR',
			'__INNER_FILTER_PERSONAL' => array(
				'OWNER_ID' => $currentUserID,
				'SCOPE'    => \CCrmMailTemplateScope::Personal,
			),
			'__INNER_FILTER_COMMON' => array(
				'SCOPE' => \CCrmMailTemplateScope::Common,
			),
		),
	),
	false, false,
	array('TITLE', 'SCOPE', 'ENTITY_TYPE_ID', 'BODY_TYPE')
);

while($mailTemplateFields = $mailTemplateResult->Fetch())
{
	$arResult['MAIL_TEMPLATE_DATA'][] = array(
		'id' => $mailTemplateFields['ID'],
		'title' => $mailTemplateFields['TITLE'],
		'scope' => $mailTemplateFields['SCOPE'],
		'entityType' => \CCrmOwnerType::InvoiceName,
		'bodyType' => \CCrmContentType::resolveName($mailTemplateFields['BODY_TYPE']),
	);
}

ob_start();
$arResult['EVENT_COUNT'] = $APPLICATION->IncludeComponent(
	'bitrix:crm.invoice.events',
	'',
	array(
		'ENTITY_TYPE' => 'INVOICE',
		'ENTITY_ID' => $arResult['ELEMENT']['ID'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => 'tab_event',
		'INTERNAL' => 'Y',
		'SHOW_INTERNAL_FILTER' => 'Y',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	false
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'DEAL_EVENT',
	'name' => GetMessage('CRM_FIELD_INVOICE_EVENT'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sVal
);

// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}
$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.invoice/include/nav.php');
?>
