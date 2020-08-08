<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('QUOTE', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult["IS_CREATE_PERMITTED"] = CCrmQuote::CheckCreatePermission($userPerms);

if(!isset($arParams['GRID_ID']) || $arParams['GRID_ID'] === '')
{
	$arParams['GRID_ID'] = 'mobile_crm_deal_list';
}

$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);

//sort
$sort = array('DATE_CREATE' => 'desc');
if (isset($gridOptions["sort_by"]) && isset($gridOptions["sort_order"]))
	$sort = array($gridOptions["sort_by"] => $gridOptions["sort_order"]);

//select
$commonSelect = array(
	'TITLE', 'STAGE_ID',
	'ENTITIES_LINKS',
	'DATE_MODIFY', 'FORMATTED_OPPORTUNITY'
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

if (!in_array('STATUS_ID', $select))
	$select[] = 'STATUS_ID';

if (isset($sort["DATE_CREATE"]) && !in_array("DATE_CREATE", $select))
	$select[] = "DATE_CREATE";

if (isset($sort["DATE_MODIFY"]) && !in_array("DATE_MODIFY", $select))
	$select[] = "DATE_MODIFY";

if (in_array("ENTITIES_LINKS", $select))
	$select = array_merge($select, array(
		'CONTACT_ID', 'CONTACT_HONORIFIC', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME', 'CONTACT_POST',
		'COMPANY_ID', 'COMPANY_TITLE',
		"DEAL_ID", "DEAL_TITLE",
		"LEAD_ID", 'LEAD_TITLE'
	));

if (in_array("FORMATTED_OPPORTUNITY", $select))
	$select = array_merge($select, array('CURRENCY_ID', 'OPPORTUNITY'));

if (in_array("ASSIGNED_BY", $select))
	$select = array_merge($select, array('ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_LAST_NAME'));

if (in_array("CREATED_BY", $select))
	$select = array_merge($select, array('CREATED_BY_ID', 'CREATED_BY_LOGIN', 'CREATED_BY_NAME', 'CREATED_BY_SECOND_NAME', 'CREATED_BY_LAST_NAME'));

if (in_array("MODIFY_BY", $select))
	$select = array_merge($select, array('MODIFY_BY_ID', 'MODIFY_BY_LOGIN', 'MODIFY_BY_NAME', 'MODIFY_BY_SECOND_NAME', 'MODIFY_BY_LAST_NAME'));

//filter
$filter = array();
if(isset($_REQUEST["search"]))
{
	CUtil::JSPostUnescape();
	$v = trim($_REQUEST["search"]);
	if (!empty($v))
	{
		$searchFilter = array(
			'%TITLE' => $v,
			'%ASSIGNED_BY_LAST_NAME' => $v,
			'%ASSIGNED_BY_NAME' => $v,
			'LOGIC' => 'OR'
		);
		if (!empty($filter))
		{
			$filter["__INNER_FILTER"] = $searchFilter;
		}
		else
		{
			$filter = $searchFilter;
		}
	}
}
$arResult['FILTER_PRESETS'] = array(
	'all' => array('name' => GetMessage('M_CRM_QUOTE_LIST_FILTER_NONE'), 'fields' => array()),
	'filter_new' => array('name' => GetMessage('M_CRM_QUOTE_LIST_PRESET_NEW'), 'fields' => array('STATUS_ID' => array('selDRAFT' => 'DRAFT'))),
	'filter_my' => array('name' => GetMessage('M_CRM_QUOTE_LIST_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID' =>  intval(CCrmSecurityHelper::GetCurrentUserID()))),
	'filter_user' => array('name' => GetMessage('M_CRM_QUOTE_LIST_PRESET_USER'), 'fields' => array())
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

	if(isset($filter['OPPORTUNITY_from']))
	{
		$filter['>=OPPORTUNITY'] = $filter['OPPORTUNITY_from'];
		unset($filter['OPPORTUNITY_from']);
	}

	if(isset($filter['OPPORTUNITY_to']))
	{
		$filter['<=OPPORTUNITY'] = $filter['OPPORTUNITY_to'];
		unset($filter['OPPORTUNITY_to']);
	}

	if(isset($filter['DATE_CREATE']))
	{
		$filter['>=DATE_CREATE'] = $filter['DATE_CREATE'];
		$filter['<=DATE_CREATE'] = CCrmDateTimeHelper::SetMaxDayTime($filter['DATE_CREATE']);
		unset($filter['DATE_CREATE']);
	}

	if(isset($filter['CLOSEDATE']))
	{
		$filter['>=CLOSEDATE'] = $filter['CLOSEDATE'];
		$filter['<=CLOSEDATE'] = CCrmDateTimeHelper::SetMaxDayTime($filter['CLOSEDATE']);
		unset($filter['CLOSEDATE']);
	}
}

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

$contactID = $arResult['CONTACT_ID'] = isset($_REQUEST['contact_id']) ? intval($_REQUEST['contact_id']) : 0;
$companyID = $arResult['COMPANY_ID'] = isset($_REQUEST['company_id']) ? intval($_REQUEST['company_id']) : 0;
$currentUserID = $arResult['USER_ID'] = intval(CCrmSecurityHelper::GetCurrentUserID());

$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : SITE_DIR.'mobile/users/?user_id=#user_id#';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);
$arParams['CONTACT_URL_TEMPLATE'] = isset($arParams['CONTACT_URL_TEMPLATE']) ? $arParams['CONTACT_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#';
$arParams['COMPANY_URL_TEMPLATE'] = isset($arParams['COMPANY_URL_TEMPLATE']) ? $arParams['COMPANY_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#';
$arParams['DEAL_URL_TEMPLATE'] = isset($arParams['DEAL_URL_TEMPLATE']) ? $arParams['DEAL_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#';
$arParams['LEAD_URL_TEMPLATE'] = isset($arParams['LEAD_URL_TEMPLATE']) ? $arParams['LEAD_URL_TEMPLATE'] : SITE_DIR.'mobile/crm/lead/?page=view&lead_id=#lead_id#';
$arParams['QUOTE_VIEW_URL_TEMPLATE'] = isset($arParams['QUOTE_VIEW_URL_TEMPLATE']) ? $arParams['QUOTE_VIEW_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/quote/?page=view&quote_id=#quote_id#';
$arParams['QUOTE_EDIT_URL_TEMPLATE'] = isset($arParams['QUOTE_EDIT_URL_TEMPLATE']) ? $arParams['QUOTE_EDIT_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/quote/?page=edit&quote_id=#quote_id#';
$arParams['QUOTE_CREATE_URL_TEMPLATE'] = isset($arParams['QUOTE_CREATE_URL_TEMPLATE']) ? $arParams['QUOTE_CREATE_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/quote/?page=edit';

$arResult["AJAX_PATH"] = '/mobile/?mobile_action=mobile_crm_quote_actions';

$finalStageID = CCrmDeal::GetFinalStageID();
$finalStageSort = CCrmDeal::GetFinalStageSort();

$arOptions = array();

//fields to show
$arResult["FIELDS"] = array();
$allFields = CCrmMobileHelper::getQuoteFields(false);
$userFields = array();
CCrmMobileHelper::getFieldUser($userFields, CCrmQuote::$sUFEntityID);
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
			"name" => GetMessage("M_CRM_QUOTE_LIST_COMPANY")
		);
		$arResult["FIELDS"]["CONTACT"] = array(
			"id" => "CONTACT",
			"name" => GetMessage("M_CRM_QUOTE_LIST_CONTACT")
		);
		$arResult["FIELDS"]["DEAL"] = array(
			"id" => "DEAL",
			"name" => GetMessage("M_CRM_QUOTE_LIST_DEAL")
		);
		$arResult["FIELDS"]["LEAD"] = array(
			"id" => "LEAD",
			"name" => GetMessage("M_CRM_QUOTE_LIST_LEAD")
		);
	}
	else
	{
		$arResult["FIELDS"][$code] = $allFields[$code];
	}
}

$arResult['STATUS_LIST'] = CCrmViewHelper::GetQuoteStatusInfos();//CCrmStatus::GetStatusList('QUOTE_STATUS');
$i=0;
$jsStatusList = array();
foreach($arResult['STATUS_LIST'] as $id => $info)
{
	if (!isset($info["COLOR"]))
	{
		$semanticId = \CAllCrmQuote::GetSemanticID($info["STATUS_ID"]);

		if ($semanticId == \Bitrix\Crm\PhaseSemantics::PROCESS)
			$info["COLOR"] = \CCrmViewHelper::PROCESS_COLOR;
		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::FAILURE)
			$info["COLOR"] = \CCrmViewHelper::FAILURE_COLOR;
		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::SUCCESS)
			$info["COLOR"] = \CCrmViewHelper::SUCCESS_COLOR;

		$arResult['STATUS_LIST'][$id]['COLOR'] = $info["COLOR"];
	}

	$jsStatusList["s".$i] = array(
		"STATUS_ID" => $info["STATUS_ID"],
		"NAME" => htmlspecialcharsbx($info["NAME"]),
		"COLOR" => $info["COLOR"]
	);
	$i++;
}

$arResult['ITEMS'] = array();

$dbRes = CCrmQuote::GetList($sort, $filter, false, $navParams, $select);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult["NAV_PARAM"] = array(
	'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
	'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
	'PAGE_NAVNUM' => intval($dbRes->NavNum),
	'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
);

$enums = array(
	'STATUS_LIST' => $arResult['STATUS_LIST'],
	'JS_STATUS_LIST' => $jsStatusList,
	'FIELDS' => array_keys($arResult["FIELDS"]),
	'CHECKBOX_USER_FIELDS' => $checkBoxUserFields
);

while($item = $dbRes->GetNext())
{
	$curStatusId = $item["STATUS_ID"];

	$isEditPermitted = CCrmQuote::CheckUpdatePermission($item['ID'], $userPerms);
	$isDeletePermitted = CCrmQuote::CheckDeletePermission($item['ID'], $userPerms);

	$enums['IS_EDIT_PERMITTED'] = $isEditPermitted;

	// try to load product rows
	if (in_array("PRODUCT_ID", $select))
	{
		$item["PRODUCT_ID"] = array();
		$arProductRows = CCrmQuote::LoadProductRows($item['ID']);
		foreach($arProductRows as $arProductRow)
		{
			$item["PRODUCT_ID"][] = $arProductRow["PRODUCT_NAME"];
		}
		$item["PRODUCT_ID"] = implode(", ", $item["PRODUCT_ID"]);
	}

	CCrmMobileHelper::PrepareQuoteItem($item, $arParams, $enums);

	$arActions = array();

/*	$arActions[] = array(
		'TEXT' => GetMessageJS("M_CRM_QUOTE_LIST_SEND"),
		'ONCLICK' => "BX.Mobile.Crm.loadPageBlank('/mobile/crm/quote/edit.php');",
	);*/

	if ($isEditPermitted)
	{
		$arActions[] = array(
			"TEXT" => GetMessage("M_CRM_QUOTE_LIST_CHANGE_STATUS"),
			"ONCLICK" => "BX.Mobile.Crm.List.showStatusList(" . $item['ID'] . ", " . CUtil::PhpToJSObject($jsStatusList) . ", 'onCrmQuoteDetailUpdate')",
		);
	}

	$buttons = "";

	if ($isEditPermitted)
	{
		$detailEditUrl = CComponentEngine::MakePathFromTemplate($arParams['QUOTE_EDIT_URL_TEMPLATE'],
			array('quote_id' => $item['ID'])
		);

		$canConvert = array();
		CCrmQuote::PrepareConversionPermissionFlags($item['ID'], $canConvert, $userPerms);

		if ($canConvert["CONVERSION_PERMITTED"])
		{
			$buttons.= "{
							title:'".GetMessageJS("M_CRM_QUOTE_LIST_CREATE_BASE")."',
							callback:function()
							{
								BX.Mobile.Crm.Quote.ListConverter.showConvertDialog('".$item['ID']."', ".CUtil::PhpToJSObject($canConvert).");
							}
						},";
		}
		$buttons.= "{
						title:'".GetMessageJS("M_CRM_QUOTE_LIST_EDIT")."',
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
						title:'".GetMessageJS("M_CRM_QUOTE_LIST_DELETE")."',
						callback:function()
						{
							BX.Mobile.Crm.deleteItem('".$item["ID"]."', '".$arResult["AJAX_PATH"]."', 'list');
						}
					}";
	}

	if (!empty($buttons))
	{
		$arActions[] = array(
			"TEXT" => GetMessage("M_CRM_QUOTE_LIST_MORE"),
			'ONCLICK' => "new BXMobileApp.UI.ActionSheet({
							buttons: [" . $buttons . "]
						}, 'actionSheet').show();",
			'DISABLE' => false
		);
	}

	$detailViewUrl = CComponentEngine::MakePathFromTemplate($arParams['QUOTE_VIEW_URL_TEMPLATE'],
		array('quote_id' => $item['ID'])
	);

	$arResult['ITEMS'][$item['ID']] = array(
		"TITLE" => $item["TITLE"],
		"ACTIONS" => $arActions,
		"FIELDS" => $item,
		"ICON_HTML" => '<span class="mobile-grid-field-title-icon" '.(!isset($arResult["FIELDS"]["STATUS_ID"]) ? 'style="background: '.$arResult['STATUS_LIST'][$curStatusId]["COLOR"].'"' : "").'>
							<img src="'.$this->getPath().'/images/icon-quote.png" srcset="'.$this->getPath().'/images/icon-quote.png 2x" alt="">
						</span>',
		"ONCLICK" => "BX.Mobile.Crm.loadPageBlank('".CUtil::JSEscape($detailViewUrl)."');",
		"DATA_ID" => "mobile-grid-item-".$item["ID"]
	);
}

//date separators for grid
if (isset($sort["DATE_CREATE"]) && in_array("DATE_CREATE", $select) || isset($sort["DATE_MODIFY"]) && in_array("DATE_MODIFY", $select))
{
	$dateSortField = isset($sort["DATE_CREATE"]) ? "DATE_CREATE" : "DATE_MODIFY";
	CCrmMobileHelper::prepareDateSeparator($dateSortField, $arResult['ITEMS']);
}

$this->IncludeComponentTemplate();

