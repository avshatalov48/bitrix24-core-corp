<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult["IS_CREATE_PERMITTED"] = CCrmCompany::CheckCreatePermission($userPerms);

if(!isset($arParams['GRID_ID']) || $arParams['GRID_ID'] === '')
{
	$arParams['GRID_ID'] = 'mobile_crm_company_list';
}

$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);

//sort
$sort = array('TITLE' => 'ASC');
if (isset($gridOptions["sort_by"]) && isset($gridOptions["sort_order"]))
	$sort = array($gridOptions["sort_by"] => $gridOptions["sort_order"]);

//select
$commonSelect = array(
	'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO',
	'ASSIGNED_BY', 'PHONE', 'EMAIL'
);
if (isset($gridOptions["fields"]) && is_array($gridOptions["fields"]))
	$commonSelect = $gridOptions["fields"];

$select = $commonSelect;

if (!in_array("ID", $select))
{
	$select[] = "ID";
}

if (!in_array("TITLE", $select))
{
	$select[] = "TITLE";
	$commonSelect[] = "TITLE";
}

if (in_array("ASSIGNED_BY", $select))
	$select = array_merge($select, array('ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_LAST_NAME'));

if (in_array("CREATED_BY", $select))
	$select = array_merge($select, array('CREATED_BY_ID', 'CREATED_BY_LOGIN', 'CREATED_BY_NAME', 'CREATED_BY_SECOND_NAME', 'CREATED_BY_LAST_NAME'));

if (in_array("MODIFY_BY", $select))
	$select = array_merge($select, array('MODIFY_BY_ID', 'MODIFY_BY_LOGIN', 'MODIFY_BY_NAME', 'MODIFY_BY_SECOND_NAME', 'MODIFY_BY_LAST_NAME'));

if (in_array("FORMATTED_REVENUE", $select))
	$select = array_merge($select, array('REVENUE', 'CURRENCY_ID'));

if (in_array("FULL_ADDRESS", $select))
	$select = array_merge($select, array('ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_REGION', 'ADDRESS_PROVINCE', 'ADDRESS_POSTAL_CODE', 'ADDRESS_COUNTRY'));

if (in_array("FULL_REG_ADDRESS", $select))
	$select = array_merge($select, array('ADDRESS_LEGAL', 'REG_ADDRESS_2', 'REG_ADDRESS_CITY', 'REG_ADDRESS_REGION', 'REG_ADDRESS_PROVINCE', 'REG_ADDRESS_POSTAL_CODE', 'REG_ADDRESS_COUNTRY'));

$filter = [
	'@CATEGORY_ID' => 0,
];
if(isset($_REQUEST["search"]))
{
	CUtil::JSPostUnescape();
	$v = trim($_REQUEST["search"]);
	if (!empty($v))
	{
		$filter = array(
			'SEARCH_CONTENT' => $v
		);
	}
}

$arResult['FILTER_PRESETS'] = array(
	'all' => array('name' => GetMessage('M_CRM_COMPANY_LIST_FILTER_NONE'), 'fields' => array()),
	'filter_my' => array('name' => GetMessage('M_CRM_COMPANY_LIST_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID' =>  intval(CCrmSecurityHelper::GetCurrentUserID()))),
	'filter_user' => array('name' => GetMessage('M_CRM_COMPANY_LIST_PRESET_USER'), 'fields' => array())
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

	if(isset($filter['DATE_CREATE']))
	{
		$filter['>=DATE_CREATE'] = $filter['DATE_CREATE'];
		$filter['<=DATE_CREATE'] = CCrmDateTimeHelper::SetMaxDayTime($filter['DATE_CREATE']);
		unset($filter['DATE_CREATE']);
	}

	if(isset($filter['DATE_MODIFY']))
	{
		$filter['>=DATE_MODIFY'] = $filter['DATE_MODIFY'];
		$filter['<=DATE_MODIFY'] = CCrmDateTimeHelper::SetMaxDayTime($filter['DATE_MODIFY']);
		unset($filter['DATE_MODIFY']);
	}
}

$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());
$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : SITE_DIR.'mobile/users/?user_id=#user_id#';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);
$arParams['COMPANY_VIEW_URL_TEMPLATE'] = isset($arParams['COMPANY_VIEW_URL_TEMPLATE']) ? $arParams['COMPANY_VIEW_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/company/?page=view&company_id=#company_id#';
$arParams['COMPANY_EDIT_URL_TEMPLATE'] = isset($arParams['COMPANY_EDIT_URL_TEMPLATE']) ? $arParams['COMPANY_EDIT_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/company/?page=edit&company_id=#company_id#';
$arParams['COMPANY_CREATE_URL_TEMPLATE'] = isset($arParams['COMPANY_CREATE_URL_TEMPLATE']) ? $arParams['COMPANY_CREATE_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/company/?page=edit';

$arResult["AJAX_PATH"] = '/mobile/?mobile_action=mobile_crm_company_actions';

//navigation
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
$CGridOptions = new CGridOptions($arParams["GRID_ID"]);
$navParams = $CGridOptions->GetNavParams($navParams);

//fields to show
$arResult["FIELDS"] = array();

$allFields = CCrmMobileHelper::getCompanyFields(false);
$userFields = array();
CCrmMobileHelper::getFieldUser($userFields, CCrmCompany::$sUFEntityID);
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

$multiFields = CCrmMobileHelper::getFieldMultiInfo();

foreach($commonSelect as $code)
{
	if (
		CCrmFieldMulti::EMAIL == $code
		|| CCrmFieldMulti::PHONE == $code
		|| CCrmFieldMulti::IM == $code
		|| CCrmFieldMulti::WEB == $code
	)
	{
		foreach($multiFields[$code] as $key => $field)
		{
			$arResult["FIELDS"][$code."_".$key] = array(
				"id" => $code."_".$key,
				"name" => $field["FULL"],
				"type" => $code
			);
		}
	}
	else
	{
		if ($code != "LOGO")
		{
			$arResult["FIELDS"][$code] = $allFields[$code];
		}
	}
}

//get items list
$arResult['ITEMS'] = array();

$dbRes = CCrmCompany::GetListEx($sort, $filter, false, $navParams, $select);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult["NAV_PARAM"] = array(
	'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
	'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
	'PAGE_NAVNUM' => intval($dbRes->NavNum),
	'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
);

$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusList('COMPANY_TYPE');
//$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusList('EMPLOYEES');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusList('INDUSTRY');

$enums = array(
	'COMPANY_TYPE' => $arResult['COMPANY_TYPE_LIST'],
	'INDUSTRY' => $arResult['INDUSTRY_LIST'],
	'FIELDS' => array_keys($arResult["FIELDS"]),
	'CHECKBOX_USER_FIELDS' => $checkBoxUserFields
);

$arCompanyID = array();
while($item = $dbRes->GetNext())
{
	$arCompanyID[] = $item['ID'];

	$isEditPermitted = CCrmCompany::CheckUpdatePermission($item['ID'], $userPerms);
	$isDeletePermitted = CCrmCompany::CheckDeletePermission($item['ID'], $userPerms);

	CCrmMobileHelper::PrepareCompanyItem(
		$item,
		$arParams,
		$enums
	);

	$arActions = array();

	/*$arActions[] = array(
		"TEXT" => GetMessageJS("M_CRM_COMPANY_DEALS"),
		"ONCLICK" => "BX.Mobile.Crm.loadPageBlank('/mobile/crm/company/view.php?company_id=".$item["ID"]."');",
	);

	$arActions[] = array(
		"TEXT" => GetMessageJS("M_CRM_COMPANY_CALLS"),
		"ONCLICK" => "BX.Mobile.Crm.loadPageBlank('/mobile/crm/company/view.php?company_id=".$item["ID"]."');",
	);*/

	$buttons = "";

	if ($isEditPermitted)
	{
		$detailEditUrl = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_EDIT_URL_TEMPLATE'],
			array('company_id' => $item['ID'])
		);
		$buttons.= "{
						title:'".GetMessageJS("M_CRM_COMPANY_LIST_EDIT")."',
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
						title:'".GetMessageJS("M_CRM_COMPANY_LIST_DELETE")."',
						callback:function()
						{
							BX.Mobile.Crm.deleteItem('".$item["ID"]."', '".$arResult["AJAX_PATH"]."', 'list');
						}
					}";
	}

	if (!empty($buttons))
	{
		$arActions[] = array(
			"TEXT" => GetMessage("M_CRM_COMPANY_LIST_MORE"),
			'ONCLICK' => "new BXMobileApp.UI.ActionSheet({
							buttons: [" . $buttons . "]
						}, 'actionSheet').show();",
			'DISABLE' => false
		);
	}
	$detailViewUrl = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_VIEW_URL_TEMPLATE'],
		array('company_id' => $item['ID'])
	);

	$arResult['ITEMS'][$item['ID']] = array(
		"TITLE" => $item["TITLE"],
		"ACTIONS" => $arActions,
		"FIELDS" => $item,
		"ICON_HTML" => (!empty($item["LOGO_SRC"])
				? '<span class="mobile-grid-field-title-logo"><img src="'.$item["LOGO_SRC"].'" alt=""></span>'
				: '<span class="mobile-grid-field-title-icon"><img src="'.$this->getPath().'/images/icon-company.png" srcset="'.$this->getPath().'/images/icon-company.png 2x" alt=""></span>'
				),
		"ONCLICK" => "BX.Mobile.Crm.loadPageBlank('".CUtil::JSEscape($detailViewUrl)."');",
		"DATA_ID" => "mobile-grid-item-".$item["ID"]
	);
}

//multi fields
$selectedMultiFields = array();

if (in_array(CCrmFieldMulti::EMAIL, $select))
	$selectedMultiFields[] = CCrmFieldMulti::EMAIL;
if (in_array(CCrmFieldMulti::PHONE, $select))
	$selectedMultiFields[] = CCrmFieldMulti::PHONE;
if (in_array(CCrmFieldMulti::IM, $select))
	$selectedMultiFields[] = CCrmFieldMulti::IM;
if (in_array(CCrmFieldMulti::WEB, $select))
	$selectedMultiFields[] = CCrmFieldMulti::WEB;

if (!empty($selectedMultiFields))
{
	// adding crm multi field to result array
	$arFmList = array();
	$dbFieldsRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arCompanyID));
	while($arMulti = $dbFieldsRes->Fetch())
	{
		if (!in_array($arMulti["TYPE_ID"], $selectedMultiFields))
			continue;

		if (isset($arResult['ITEMS'][$arMulti['ELEMENT_ID']]))
			$arResult['ITEMS'][$arMulti['ELEMENT_ID']]["FIELDS"][$arMulti['COMPLEX_ID']] = htmlspecialcharsbx($arMulti['VALUE']);
	}
}

//date separators for grid
if (isset($sort["DATE_CREATE"]) && in_array("DATE_CREATE", $select) || isset($sort["DATE_MODIFY"]) && in_array("DATE_MODIFY", $select))
{
	$dateSortField = isset($sort["DATE_CREATE"]) ? "DATE_CREATE" : "DATE_MODIFY";
	CCrmMobileHelper::prepareDateSeparator($dateSortField, $arResult['ITEMS']);
}

$this->IncludeComponentTemplate();
