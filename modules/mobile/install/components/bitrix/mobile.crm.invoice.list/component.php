<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}
$arResult["IS_CREATE_PERMITTED"] = CCrmInvoice::CheckCreatePermission($userPerms);

if(!isset($arParams['GRID_ID']) || $arParams['GRID_ID'] === '')
{
	$arParams['GRID_ID'] = 'mobile_crm_invoice_list';
}

$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);

$sort = array('ID' => 'ASC');
if (isset($gridOptions["sort_by"]) && isset($gridOptions["sort_order"]))
	$sort = array($gridOptions["sort_by"] => $gridOptions["sort_order"]);

$commonSelect = array(
	'ACCOUNT_NUMBER','STATUS_ID', 'ORDER_TOPIC',
	'ENTITIES_LINKS',
	'FORMATTED_PRICE',
	'RESPONSIBLE'
);

if (isset($gridOptions["fields"]) && is_array($gridOptions["fields"]))
	$commonSelect = $gridOptions["fields"];

$select = $commonSelect;

if (!in_array('ID', $select))
	$select[] = 'ID';

if (!in_array("ORDER_TOPIC", $select))
{
	$select[] = "ORDER_TOPIC";
	$commonSelect[] = "ORDER_TOPIC";
}
if (!in_array('STATUS_ID', $select))
	$select[] = 'STATUS_ID';

if (in_array("ENTITIES_LINKS", $select))
	$select = array_merge($select, array('UF_DEAL_ID', 'UF_COMPANY_ID', 'UF_CONTACT_ID', 'UF_QUOTE_ID'));

if (in_array("FORMATTED_PRICE", $select))
	$select = array_merge($select, array('PRICE', 'CURRENCY'));

if (in_array("RESPONSIBLE", $select))
	$select = array_merge($select, array('RESPONSIBLE_ID', 'RESPONSIBLE_LOGIN', 'RESPONSIBLE_NAME', 'RESPONSIBLE_LAST_NAME', 'RESPONSIBLE_SECOND_NAME'));

$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : SITE_DIR.'mobile/users/?user_id=#user_id#';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);
$arParams['CONTACT_URL_TEMPLATE'] = isset($arParams['CONTACT_URL_TEMPLATE']) ? $arParams['CONTACT_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#';
$arParams['COMPANY_URL_TEMPLATE'] = isset($arParams['COMPANY_URL_TEMPLATE']) ? $arParams['COMPANY_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#';
$arParams['DEAL_URL_TEMPLATE'] = isset($arParams['DEAL_URL_TEMPLATE']) ? $arParams['DEAL_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#';
$arParams['QUOTE_URL_TEMPLATE'] = isset($arParams['QUOTE_URL_TEMPLATE']) ? $arParams['QUOTE_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/quote/?page=view&quote_id=#quote_id#';
$arParams['INVOICE_VIEW_URL_TEMPLATE'] = isset($arParams['INVOICE_VIEW_URL_TEMPLATE']) ? $arParams['INVOICE_VIEW_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/invoice/?page=view&invoice_id=#invoice_id#';
$arParams['INVOICE_EDIT_URL_TEMPLATE'] = isset($arParams['INVOICE_EDIT_URL_TEMPLATE']) ? $arParams['INVOICE_EDIT_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/invoice/?page=edit&invoice_id=#invoice_id#';
$arParams['INVOICE_CREATE_URL_TEMPLATE'] = isset($arParams['INVOICE_CREATE_URL_TEMPLATE']) ? $arParams['INVOICE_CREATE_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/invoice/?page=edit';

$arResult["AJAX_PATH"] = '/mobile/?mobile_action=mobile_crm_invoice_actions';

$filter = array();
if(isset($_REQUEST["search"]))
{
	CUtil::JSPostUnescape();
	$v = trim($_REQUEST["search"]);
	if (!empty($v))
	{
		$filter['%ORDER_TOPIC'] = $v;
	}
}

$arResult['FILTER_PRESETS'] = array(
	'all' => array('name' => GetMessage('M_CRM_INVOICE_LIST_FILTER_NONE'), 'fields' => array()),
	'filter_my_unpaid' => array('name' => GetMessage('M_CRM_INVOICE_LIST_PRESET_MY_UNPAID'), 'fields' => array('STATUS_ID'=> CCrmStatusInvoice::getStatusIds('neutral'))),
	'filter_my_paid' => array('name' => GetMessage('M_CRM_INVOICE_LIST_PRESET_MY_PAID'), 'fields' => array('STATUS_ID'=> CCrmStatusInvoice::getStatusIds('success'))),
	'filter_user' => array('name' => GetMessage('M_CRM_LEAD_LIST_PRESET_USER'), 'fields' => array())
);

if (isset($gridOptions['filters']['filter_user']))
{
	foreach($gridOptions['filters']['filter_user']['fields'] as $field => $value)
	{
		if ($value !== "")
			$arResult['FILTER_PRESETS']['filter_user']['fields'][$field] = $value;
	}
}

$arResult["CURRENT_FILTER"] = "all";
if (isset($gridOptions["currentFilter"]) && in_array($gridOptions["currentFilter"], array_keys($arResult['FILTER_PRESETS'])))
{
	$filter = array_merge($filter, $arResult['FILTER_PRESETS'][$gridOptions["currentFilter"]]['fields']);
	$arResult["CURRENT_FILTER"] = $gridOptions["currentFilter"];

	if(isset($filter['TITLE']))
	{
		$filter['%TITLE'] = $filter['TITLE'];
		unset($filter['TITLE']);
	}
}

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}

$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => true,
	'bShowAll' => false
);
$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CCrmGridOptions($arParams["GRID_ID"]);
$navParams = $CGridOptions->GetNavParams($navParams);

//fields to show
$arResult["FIELDS"] = array();
$allFields = CCrmMobileHelper::getInvoiceFields(false);
$userFields = array();
CCrmMobileHelper::getFieldUser($userFields, CCrmInvoice::$sUFEntityID);
$allFields = array_merge($allFields, $userFields);

$checkBoxUserFields = array();
if (!empty($userFields))
{
	foreach($userFields as $fieldId => $info)
	{
		if ($info['type'] == 'CHECKBOX')
		{
			$checkBoxUserFields[] = $fieldId;
		}
	}
}

foreach($commonSelect as $code)
{
	if ($code == "ENTITIES_LINKS")
	{
		$arResult["FIELDS"]["COMPANY"] = array(
			"id" => "COMPANY",
			"name" => GetMessage("M_CRM_INVOICE_LIST_COMPANY")
		);
		$arResult["FIELDS"]["CONTACT"] = array(
			"id" => "CONTACT",
			"name" => GetMessage("M_CRM_INVOICE_LIST_CONTACT")
		);
		$arResult["FIELDS"]["DEAL"] = array(
			"id" => "DEAL",
			"name" => GetMessage("M_CRM_INVOICE_LIST_DEAL")
		);
		$arResult["FIELDS"]["QUOTE"] = array(
			"id" => "QUOTE",
			"name" => GetMessage("M_CRM_INVOICE_LIST_QUOTE")
		);
	}
	else
	{
		$arResult["FIELDS"][$code] = $allFields[$code];
	}
}

$arResult["STATUS_LIST"] = CCrmViewHelper::GetInvoiceStatusInfos();//CCrmStatusInvoice::getStatusList();
$i=0;
$allStatusList = array();
foreach($arResult["STATUS_LIST"] as $id => $info)
{
	if (!isset($info["COLOR"]))
	{
		$semanticId = \CAllCrmInvoice::GetSemanticID($info["STATUS_ID"]);

		if ($semanticId == \Bitrix\Crm\PhaseSemantics::PROCESS)
			$info["COLOR"] = \CCrmViewHelper::PROCESS_COLOR;
		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::FAILURE)
			$info["COLOR"] = \CCrmViewHelper::FAILURE_COLOR;
		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::SUCCESS)
			$info["COLOR"] = \CCrmViewHelper::SUCCESS_COLOR;

		$arResult['STATUS_LIST'][$id]['COLOR'] = $info["COLOR"];
	}

	$allStatusList["s".$i] = array(
		"STATUS_ID" => $info["STATUS_ID"],
		"NAME" => htmlspecialcharsbx($info["NAME"]),
		"COLOR" => $info["COLOR"]
	);
	$i++;
}

$arResult['ITEMS'] = array();

$dbRes = CCrmInvoice::GetList($sort, $filter, false, $navParams, $select, array());
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult["NAV_PARAM"] = array(
	'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
	'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
	'PAGE_NAVNUM' => intval($dbRes->NavNum),
	'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
);

$enums = array(
	'FIELDS' => array_keys($arResult["FIELDS"]),
	'CHECKBOX_USER_FIELDS' => $checkBoxUserFields
);
if (in_array("STATUS_ID", $select))
{
	$enums["STATUS_LIST"] = $arResult["STATUS_LIST"];
}
if (in_array("PERSON_TYPE_ID", $select))
{
	$enums["PERSON_TYPES"] = CCrmPaySystem::getPersonTypesList($personTypeID);
	if (in_array("PAY_SYSTEM_ID", $select))
	{
		foreach($enums["PERSON_TYPES"] as $id => $name)
		{
			$enums["PAY_SYSTEMS"][$id] = CCrmPaySystem::GetPaySystemsListItems($id);
		}
	}
}

while($item = $dbRes->GetNext())
{
	$curStatusId = $item["STATUS_ID"];
	$isEditPermitted = CCrmInvoice::CheckUpdatePermission($item['ID'], $userPerms);
	$isDeletePermitted = CCrmInvoice::CheckDeletePermission($item['ID'], $userPerms);

	$enums['IS_EDIT_PERMITTED'] = $isEditPermitted;

	CCrmMobileHelper::PrepareInvoiceItem($item, $arParams, $enums);

	$arActions = array();

	/*$arActions[] = array(
		'TEXT' => GetMessageJS("M_CRM_INVOICE_LIST_SEND"),
		'ONCLICK' => "BX.Mobile.Crm.loadPageBlank('/mobile/crm/invoice/edit.php');",
		'DISABLE' => false
	);*/

	if ($isEditPermitted && is_array($allStatusList))
	{
		$arActions[] = array(
			"TEXT" => GetMessageJS("M_CRM_INVOICE_LIST_CHANGE_STATUS"),
			"ONCLICK" => "BX.Mobile.Crm.List.showStatusList(".$item['ID'].", ".CUtil::PhpToJSObject($allStatusList).", 'onCrmInvoiceDetailUpdate')",
		);
	}

	$buttons = "";

	if ($isEditPermitted)
	{
		$detailEditUrl = CComponentEngine::MakePathFromTemplate($arParams['INVOICE_EDIT_URL_TEMPLATE'],
			array('invoice_id' => $item['ID'])
		);

		$buttons.= "{
						title:'".GetMessageJS("M_CRM_INVOICE_LIST_EDIT")."',
						callback:function()
						{
							BXMobileApp.PageManager.loadPageModal({
								url: '".CUtil::JSEscape($detailEditUrl)."'
							});
						}
					},";
	}
	if ($isDeletePermitted)
	{
		$buttons.= "{
						title:'".GetMessageJS("M_CRM_INVOICE_LIST_DELETE")."',
						callback:function()
						{
							BX.Mobile.Crm.deleteItem('".$item["ID"]."', '".$arResult["AJAX_PATH"]."', 'list');
						}
					}";
	}

	if (!empty($buttons))
	{
		$arActions[] = array(
			"TEXT" => GetMessageJS("M_CRM_INVOICE_LIST_MORE"),
			'ONCLICK' => "new BXMobileApp.UI.ActionSheet({
							buttons: [" . $buttons . "]
						}, 'actionSheet').show();",
			'DISABLE' => false
		);
	}

	$detailViewUrl = CComponentEngine::MakePathFromTemplate($arParams['INVOICE_VIEW_URL_TEMPLATE'],
		array('invoice_id' => $item['ID'])
	);

	$arResult['ITEMS'][$item['ID']] = array(
		"TITLE" => $item["ORDER_TOPIC"],
		"ACTIONS" => $arActions,
		"FIELDS" => $item,
		"ICON_HTML" => '<span class="mobile-grid-field-title-icon" '.(!isset($arResult["FIELDS"]["STATUS_ID"]) ? 'style="background: '.$arResult["STATUS_LIST"][$curStatusId]["COLOR"].'"' : "").'>
						<img src="'.$this->getPath().'/images/icon-invoice.png" srcset="'.$this->getPath().'/images/icon-invoice.png 2x" alt="">
					</span>',
		"ONCLICK" => "BX.Mobile.Crm.loadPageBlank('".CUtil::JSEscape($detailViewUrl)."');",
		"DATA_ID" => "mobile-grid-item-".$item["ID"]
	);
}
/*
$arResult['PERMISSIONS'] = array(
	'CREATE' => !$userPerms->HavePerm('INVOICE', BX_CRM_PERM_NONE, 'ADD')
);*/

//date separators for grid
if (isset($sort["DATE_INSERT"]) && in_array("DATE_INSERT", $select) || isset($sort["DATE_UPDATE"]) && in_array("DATE_UPDATE", $select))
{
	$dateSortField = isset($sort["DATE_INSERT"]) ? "DATE_INSERT" : "DATE_UPDATE";
	CCrmMobileHelper::prepareDateSeparator($dateSortField, $arResult['ITEMS']);
}

$this->IncludeComponentTemplate();

