<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if ($userPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}
$arResult["IS_CREATE_PERMITTED"] = CCrmLead::CheckCreatePermission($userPerms);

if(!isset($arParams['GRID_ID']) || $arParams['GRID_ID'] === '')
{
	$arParams['GRID_ID'] = 'mobile_crm_lead_list';
}

$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $arParams["GRID_ID"]);

//sort
$sort = array('DATE_CREATE' => 'desc');
if (isset($gridOptions["sort_by"]) && isset($gridOptions["sort_order"]))
	$sort = array($gridOptions["sort_by"] => $gridOptions["sort_order"]);

//select
$commonSelect = array(
	'ID', 'TITLE', 'STATUS_ID',	'DATE_CREATE',
	'NAME', 'SECOND_NAME', 'LAST_NAME'
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

if (in_array("ASSIGNED_BY", $select))
	$select = array_merge($select, array('ASSIGNED_BY_ID', 'ASSIGNED_BY_LOGIN', 'ASSIGNED_BY_NAME', 'ASSIGNED_BY_SECOND_NAME', 'ASSIGNED_BY_LAST_NAME'));

if (in_array("CREATED_BY", $select))
	$select = array_merge($select, array('CREATED_BY_ID', 'CREATED_BY_LOGIN', 'CREATED_BY_NAME', 'CREATED_BY_SECOND_NAME', 'CREATED_BY_LAST_NAME'));

if (in_array("MODIFY_BY", $select))
	$select = array_merge($select, array('MODIFY_BY_ID', 'MODIFY_BY_LOGIN', 'MODIFY_BY_NAME', 'MODIFY_BY_SECOND_NAME', 'MODIFY_BY_LAST_NAME'));

if (in_array("FORMATTED_OPPORTUNITY", $select))
	$select = array_merge($select, array('CURRENCY_ID', 'OPPORTUNITY'));

if (in_array("FULL_ADDRESS", $select))
	$select = array_merge($select, array('ADDRESS', 'ADDRESS_2', 'ADDRESS_CITY', 'ADDRESS_REGION', 'ADDRESS_PROVINCE', 'ADDRESS_POSTAL_CODE', 'ADDRESS_COUNTRY'));

//filter
$filter = array();

$arResult['FILTER_PRESETS'] = array(
	'all' => array('name' => GetMessage('M_CRM_LEAD_LIST_NO_FILTER'), 'fields' => array()),
	'filter_new' => array('name' => GetMessage('M_CRM_LEAD_LIST_PRESET_NEW'), 'fields' => array('STATUS_ID' => array('NEW'))),
	'filter_my' => array('name' => GetMessage('M_CRM_LEAD_LIST_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID' =>  intval(CCrmSecurityHelper::GetCurrentUserID()))),
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

	if(isset($filter['DATE_CREATE']))
	{
		$filter['>=DATE_CREATE'] = $filter['DATE_CREATE'];
		$filter['<=DATE_CREATE'] = CCrmDateTimeHelper::SetMaxDayTime($filter['DATE_CREATE']);
		unset($filter['DATE_CREATE']);
	}
}

if(isset($_REQUEST["search"]))
{
	$v = trim($_REQUEST["search"]);
	if (!empty($v))
	{
		$searchFilter = array(
			'%TITLE' => $v,
			'%FULL_NAME' => $v,
			'%COMPANY_TITLE' => $v,
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

$arParams['USER_PROFILE_URL_TEMPLATE'] = isset($arParams['USER_PROFILE_URL_TEMPLATE']) ? $arParams['USER_PROFILE_URL_TEMPLATE'] : SITE_DIR.'mobile/users/?user_id=#user_id#';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['LEAD_VIEW_URL_TEMPLATE'] = isset($arParams['LEAD_VIEW_URL_TEMPLATE']) ? $arParams['LEAD_VIEW_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/lead/?page=view&lead_id=#lead_id#';
$arParams['LEAD_EDIT_URL_TEMPLATE'] = isset($arParams['LEAD_EDIT_URL_TEMPLATE']) ? $arParams['LEAD_EDIT_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/lead/?page=edit&lead_id=#lead_id#';
$arParams['LEAD_CREATE_URL_TEMPLATE'] = isset($arParams['LEAD_CREATE_URL_TEMPLATE']) ? $arParams['LEAD_CREATE_URL_TEMPLATE'] : SITE_DIR.'/mobile/crm/lead/?page=edit';

$arResult["AJAX_PATH"] = '/mobile/?mobile_action=mobile_crm_lead_actions';

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

$allFields = CCrmMobileHelper::getLeadFields(false);
$userFields = array();
CCrmMobileHelper::getFieldUser($userFields, CCrmLead::$sUFEntityID);
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
		$arResult["FIELDS"][$code] = $allFields[$code];
	}
}

$sourceList = CCrmStatus::GetStatusListEx('SOURCE');
//status
$allStatusList = CCrmViewHelper::GetLeadStatusInfos();//CCrmStatus::GetStatusList('STATUS');
$declineStatusList = array();
$isDeclineStatus = false;
$i=0;
foreach($allStatusList as $id => $info)
{
	if (!isset($info["COLOR"]))
	{
		$semanticId = \CAllCrmLead::GetSemanticID($info["STATUS_ID"]);

		if ($semanticId == \Bitrix\Crm\PhaseSemantics::PROCESS)
			$info["COLOR"] = \CCrmViewHelper::PROCESS_COLOR;
		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::FAILURE)
			$info["COLOR"] = \CCrmViewHelper::FAILURE_COLOR;
		else if ($semanticId == \Bitrix\Crm\PhaseSemantics::SUCCESS)
			$info["COLOR"] = \CCrmViewHelper::SUCCESS_COLOR;

		$allStatusList[$id]['COLOR'] = $info["COLOR"];
	}

	if ($id == "JUNK")
		$isDeclineStatus = true;

	if (!$isDeclineStatus)
		continue;

	$declineStatusList["s".$i] = array(
		"STATUS_ID" => $info["STATUS_ID"],
		"NAME" => htmlspecialcharsbx($info["NAME"]),
		"COLOR" => $info["COLOR"]
	);
	$i++;
}

//get items list
$arResult['ITEMS'] = array();
$dbRes = CCrmLead::GetListEx($sort, $filter, false, $navParams, $select);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult["NAV_PARAM"] = array(
	'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
	'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
	'PAGE_NAVNUM' => intval($dbRes->NavNum),
	'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
);

$enums = array(
	'STATUS_LIST' => $allStatusList,
	'FIELDS' => array_keys($arResult["FIELDS"]),
	'SOURCE_LIST' => $sourceList,
	'CHECKBOX_USER_FIELDS' => $checkBoxUserFields
);

$arLeadID = array();
while($item = $dbRes->GetNext())
{
	$arLeadID[] = $item['ID'];
	$curStatusId = $item["STATUS_ID"];
	$isEditPermitted = CCrmLead::CheckUpdatePermission($item['ID'], $userPerms);
	$isDeletePermitted = CCrmLead::CheckDeletePermission($item['ID'], $userPerms);

	// try to load product rows
	if (in_array("PRODUCT_ID", $select))
	{
		$item["PRODUCT_ID"] = array();
		$arProductRows = CCrmLead::LoadProductRows($item['ID']);
		foreach($arProductRows as $arProductRow)
		{
			$item["PRODUCT_ID"][] = $arProductRow["PRODUCT_NAME"];
		}
		$item["PRODUCT_ID"] = implode(", ", $item["PRODUCT_ID"]);
	}

	$arActions = array();

	$enums['IS_EDIT_PERMITTED'] = $isEditPermitted;

	CCrmMobileHelper::PrepareLeadItem(
		$item,
		$arParams,
		$enums
	);

	if ($isEditPermitted && $curStatusId != "CONVERTED")
	{
		if (count($declineStatusList) > 1)
		{
			$arActions[] = array(
				"TEXT" => GetMessage("M_CRM_LEAD_LIST_DECLINE"),
				"ONCLICK" => "BX.Mobile.Crm.List.showStatusList(" . $item['ID'] . ", " . CUtil::PhpToJSObject($declineStatusList) . ")",
				'DISABLE' => in_array($curStatusId, array_keys($declineStatusList)) ? true : false
			);
		}
		else
		{
			$arActions[] = array(
				"TEXT" => GetMessage("M_CRM_LEAD_LIST_JUNK"),
				"ONCLICK" => "BX.Mobile.Crm.List.changeStatus('" . $item["ID"] . "', " . CUtil::PhpToJSObject(array(
							"STATUS_ID" => $allStatusList["JUNK"]["STATUS_ID"],
							"NAME" => $allStatusList["JUNK"]["NAME"],
							"COLOR" => $allStatusList["JUNK"]["COLOR"])
					) . ")",
				'DISABLE' => in_array($curStatusId, array_keys($declineStatusList)) ? true : false
			);
		}
	}

	$detailEditUrl = CComponentEngine::MakePathFromTemplate($arParams['LEAD_EDIT_URL_TEMPLATE'],
		array('lead_id' => $item['ID'])
	);
	$detailViewUrl = CComponentEngine::MakePathFromTemplate($arParams['LEAD_VIEW_URL_TEMPLATE'],
		array('lead_id' => $item['ID'])
	);

	$canConvert = array();
	CCrmLead::PrepareConversionPermissionFlags($item['ID'], $canConvert, $userPerms);

	if ($isEditPermitted && $canConvert["CONVERSION_PERMITTED"])
	{
		$arActions[] = array(
			'TEXT' => GetMessage("M_CRM_LEAD_LIST_CREATE_BASE"),
			'ONCLICK' => "BX.Mobile.Crm.Lead.ListConverter.showConvertDialog('".$item['ID']."', ".CUtil::PhpToJSObject($canConvert).");",
			'DISABLE' => false
		);
	}

	$buttons = "";

	/*$buttons.= {
					title:'".GetMessageJS("M_CRM_LEAD_LIST_ADD_DEAL")."',
					callback:function()
					{
						new BXMobileApp.UI.ActionSheet({
							buttons: [
								{
									title: '".GetMessageJS("M_CRM_LEAD_LIST_CALL")."',
									callback:function()
									{
										BX.Mobile.Crm.loadPageBlank('');
									}
								},
								{
									title: '".GetMessageJS("M_CRM_LEAD_LIST_MEETING")."',
									callback:function()
									{
										BX.Mobile.Crm.loadPageBlank('');
									}
								},
								{
									title: '".GetMessageJS("M_CRM_LEAD_LIST_MAIL")."',
									callback:function()
									{
										BX.Mobile.Crm.loadPageBlank('');
									}
								}
							]
						}, 'actionSheetLead').show();
					}
				},*/

	if ($isEditPermitted)
	{
		$buttons.= "{
						title:'".GetMessageJS("M_CRM_LEAD_LIST_EDIT")."',
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
						title:'".GetMessageJS("M_CRM_LEAD_LIST_DELETE")."',
						callback:function()
						{
							BX.Mobile.Crm.deleteItem('".$item["ID"]."', '".$arResult["AJAX_PATH"]."', 'list');
						}
					}";
	}

	if (!empty($buttons))
	{
		$arActions[] = array(
			"TEXT" => GetMessage("M_CRM_LEAD_LIST_MORE"),
			'ONCLICK' => "new BXMobileApp.UI.ActionSheet({
							buttons: [" . $buttons . "]
						}, 'actionSheet').show();",
			'DISABLE' => false
		);
	}

	$arResult['ITEMS'][$item['ID']] = array(
		"TITLE" => $item["TITLE"],
		"ACTIONS" => $arActions,
		"FIELDS" => $item,
		"ICON_HTML" => '<span class="mobile-grid-field-title-icon" '.(!isset($arResult["FIELDS"]["STATUS_ID"]) ? 'style="background: '.$allStatusList[$curStatusId]["COLOR"].'"' : "").'>
							<img src="'.$this->getPath().'/images/icon-lead2x.png" srcset="'.$this->getPath().'/images/icon-lead2x.png 2x" alt="">
						</span>',
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
	$dbFieldsRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arLeadID));
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

