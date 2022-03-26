<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$entityType = isset($arParams['ENTITY_TYPES']) ? $arParams['ENTITY_TYPES'] : "";
if(empty($entityType) && isset($_REQUEST['entityTypes']) && !empty($_REQUEST['entityTypes']))
{
	$entityType = $_REQUEST['entityTypes'];
}
/*
if(empty($entityType))
{
	$entityTypes = array(
		CCrmOwnerType::ContactName,
		CCrmOwnerType::CompanyName,
		CCrmOwnerType::LeadName,
		CCrmOwnerType::DealName,
		CCrmOwnerType::QuoteName
	);
}*/

if (!isset($arParams["EVENT_NAME"]))
	$arParams["EVENT_NAME"] = "";

$effectiveEntityTypes = array();
$userPerms = CCrmPerms::GetCurrentUserPermissions();

$entityTypeName = mb_strtoupper($entityType);
if(CCrmAuthorizationHelper::CheckReadPermission($entityTypeName, 0, $userPerms))
{
	$effectiveEntityType = $entityTypeName;
}

if(empty($effectiveEntityType))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if (isset($_REQUEST["pageId"]))
	$arResult["pageId"] = $_REQUEST["pageId"];
else
	$arResult["pageId"] = "";

$arParams['ENABLE_CREATION'] = $arResult['ENABLE_CREATION'] = isset($arParams['ENABLE_CREATION']) ? (bool)$arParams['ENABLE_CREATION'] : true;
$arParams['COMPANY_EDIT_URL_TEMPLATE'] = isset($arParams['COMPANY_EDIT_URL_TEMPLATE']) ? $arParams['COMPANY_EDIT_URL_TEMPLATE'] : '';
$arParams['CONTACT_EDIT_URL_TEMPLATE'] = isset($arParams['CONTACT_EDIT_URL_TEMPLATE']) ? $arParams['CONTACT_EDIT_URL_TEMPLATE'] : '';
$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$selectedEntityType = isset($arParams['SELECTED_ENTITY_TYPE']) ? $arParams['SELECTED_ENTITY_TYPE'] : '';
/*if($selectedEntityType === '' || !in_array($effectiveEntityTypes, $entityTypes, true))
{
	$selectedEntityType = $effectiveEntityTypes[0];
}*/
$arResult['SELECTED_ENTITY_TYPE'] = $arParams['SELECTED_ENTITY_TYPE'] = $selectedEntityType;

global $APPLICATION;

$arResult['SCOPE'] = $arParams['SCOPE'] = $scope;

$arResult['EFFECTIVE_ENTITY_TYPES'] = $effectiveEntityTypes;
$arResult['ENTITY_DATA'] = array();

$contactSort = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC', 'SECOND_NAME' => 'ASC');
$companySort = array('TITLE' => 'ASC');
$leadSort = array('DATE_CREATE' => 'ASC');
$dealSort = array('DATE_CREATE' => 'ASC');

$contactSelect = array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'POST', 'PHOTO', 'COMPANY_ID', 'COMPANY_TITLE');
$companySelect = array('ID', 'TITLE', 'LOGO', 'COMPANY_TYPE');
$leadSelect = array('ID', 'TITLE', 'STATUS_ID');
$dealSelect = array('ID', 'TITLE', 'STAGE_ID');

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

// CONTACT -->
if(CCrmOwnerType::ContactName == $effectiveEntityType)
{
	$sort = $contactSort;
	$select = $contactSelect;

	$filter = [];
	if(isset($_REQUEST["search"]))
	{
		CUtil::JSPostUnescape();
		$v = trim($_REQUEST["search"]);
		if (!empty($v))
		{
			$filter['%TITLE'] = $v;
			$filter['%FULL_NAME'] = $v;
			$filter['%COMPANY_TITLE'] = $v;
			$filter['LOGIC'] = 'OR';
		}
	}

//fields to show
	$arResult["FIELDS"] = array(
		'CONTACT_COMPANY' => array('id' => 'CONTACT_COMPANY', 'name' => GetMessage('CRM_COLUMN_CONTACT_CONTACT_COMPANY_INFO')),
		'POST' => array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_CONTACT_POST')),
		'COMPANY_ID' => array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_CONTACT_COMPANY_ID')),
	);
	$multiFields = CCrmMobileHelper::getFieldMultiInfo();

	foreach($multiFields as $code => $info)
	{
		if (
			CCrmFieldMulti::EMAIL == $code
			|| CCrmFieldMulti::PHONE == $code
		)
		{
			foreach($multiFields[$code] as $key => $field)
			{
				$arResult["FIELDS"][$code."_".$key] = array(
					"id" => $code."_".$key,
					"name" => $field["FULL"],
					//"type" => $code
				);
			}
		}
	}

	$dbRes = CCrmContact::GetListEx($sort, $filter, false, $navParams, $select);
	$dbRes->NavStart($navParams['nPageSize'], false);

	$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
	$arResult["NAV_PARAM"] = array(
		'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
		'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
		'PAGE_NAVNUM' => intval($dbRes->NavNum),
		'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
	);

	$itemData = array();
	$arContactIds = array();
	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareContactItem($item, $arParams);

		$arContactIds[] = $item["ID"];

		$contactShowUrl = CComponentEngine::MakePathFromTemplate($arParams['CONTACT_SHOW_URL_TEMPLATE'],
			array('contact_id' => $item['ID'])
		);

		$itemData[$item['ID']] = array(
			"id" => $item['ID'],
			"name" => $item['FORMATTED_NAME'],
			"addTitle" => $item['POST'],
			"url" => $contactShowUrl,
			"entityType" => "contact"
		);

		if (!empty($item["PHOTO_SRC"]))
		{
			$itemData[$item['ID']]['image'] = $item["PHOTO_SRC"];
		}

		$arResult['ITEMS'][$item['ID']] = array(
			"TITLE" => $item["FORMATTED_NAME"],
			"FIELDS" => $item,
			"ICON_HTML" => (!empty($item["PHOTO_SRC"])
				? '<span class="mobile-grid-field-title-logo"><img src="'.$item["PHOTO_SRC"].'" alt=""></span>'
				: '<span class="mobile-grid-field-title-icon"><img src="'.$this->getPath().'/images/icon-contact.png" srcset="'.$this->getPath().'/images/icon-contact.png 2x" alt=""></span>'
			),
			"DATA_ID" => "mobile-grid-item-".$item["ID"]
		);
	}

	if (!empty($arContactIds))
	{
		//multi fields
		$dbFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ContactName,
				'ELEMENT_ID' => $arContactIds
			)
		);
		while ($arMulti = $dbFields->Fetch())
		{
			if (!in_array($arMulti["TYPE_ID"], array(CCrmFieldMulti::PHONE, CCrmFieldMulti::EMAIL)))
				continue;

			if (isset($arResult['ITEMS'][$arMulti['ELEMENT_ID']]))
				$arResult['ITEMS'][$arMulti['ELEMENT_ID']]["FIELDS"][$arMulti['COMPLEX_ID']] = htmlspecialcharsbx($arMulti['VALUE']);

			$itemData[$arMulti['ELEMENT_ID']]["multi"][] = array(
				"name" => $arResult["FIELDS"][$arMulti['COMPLEX_ID']]["name"],
				"value" => htmlspecialcharsbx($arMulti['VALUE']),
				"type" => $arMulti["TYPE_ID"]
			);
		}

		foreach ($itemData as $id => $data)
		{
			$data["pageId"] = $arResult["pageId"];
			$data["entity"] = "contact";
			$arResult['ITEMS'][$id]["ONCLICK"] = "BX.Mobile.Crm.EntityList.selectItem('" . htmlspecialcharsbx(CUtil::JSEscape($arParams["EVENT_NAME"])) . "', " . CUtil::PhpToJSObject($data) . ")";
		}
	}

/*	if($arParams['ENABLE_CREATION'])
	{
		$arResult['ENTITY_DATA'][CCrmOwnerType::ContactName]['CREATE_URL'] = $arParams['CONTACT_EDIT_URL_TEMPLATE'] !== ''
			? CComponentEngine::MakePathFromTemplate(
				$arParams['CONTACT_EDIT_URL_TEMPLATE'],
				array(
					'contact_id' => 0,
					'context_id' => $contextID
				)
			) : '';
	}*/
}
//<-- CONTACT
// COMPANY -->
if (CCrmOwnerType::CompanyName == $effectiveEntityType)
{
	$sort = $companySort;
	$select = $companySelect;
	$filter = array();
	if(isset($_REQUEST["search"]))
	{
		CUtil::JSPostUnescape();
		$v = trim($_REQUEST["search"]);
		if (!empty($v))
		{
			$filter['%TITLE'] = $v;
			$filter['LOGIC'] = 'OR';
		}
	}

//fields to show
	$arResult["FIELDS"] = array(
		'COMPANY_TYPE' => array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMPANY_TYPE')),
	);
	$multiFields = CCrmMobileHelper::getFieldMultiInfo();

	foreach($multiFields as $code => $info)
	{
		if (
			CCrmFieldMulti::EMAIL == $code
			|| CCrmFieldMulti::PHONE == $code
		)
		{
			foreach($multiFields[$code] as $key => $field)
			{
				$arResult["FIELDS"][$code."_".$key] = array(
					"id" => $code."_".$key,
					"name" => $field["FULL"],
					//"type" => $code
				);
			}
		}
	}

	$dbRes = CCrmCompany::GetListEx($sort, $filter, false, $navParams, $select);
	$dbRes->NavStart($navParams['nPageSize'], false);

	$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
	$arResult["NAV_PARAM"] = array(
		'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
		'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
		'PAGE_NAVNUM' => intval($dbRes->NavNum),
		'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
	);

	$itemData = array();
	$arCompanyIds = array();
	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareCompanyItem($item, $arParams);

		$arCompanyIds[] = $item["ID"];

		$companyShowUrl = CComponentEngine::MakePathFromTemplate($arParams['COMPANY_SHOW_URL_TEMPLATE'],
			array('company_id' => $item['ID'])
		);
		$itemData[$item['ID']] = array(
			"id" => $item['ID'],
			"name" => $item['TITLE'],
			"addTitle" => $item['COMPANY_TYPE'],
			"url" => $companyShowUrl,
			"entityType" => "company"
		);

		if (!empty($item["LOGO_SRC"]))
		{
			$itemData[$item['ID']]['image'] = $item["LOGO_SRC"];
		}

		$arResult['ITEMS'][$item['ID']] = array(
			"TITLE" => $item["TITLE"],
			"FIELDS" => $item,
			"ICON_HTML" => (!empty($item["LOGO_SRC"])
				? '<span class="mobile-grid-field-title-logo"><img src="'.$item["LOGO_SRC"].'" alt=""></span>'
				: '<span class="mobile-grid-field-title-icon"><img src="'.$this->getPath().'/images/icon-company.png" srcset="'.$this->getPath().'/images/icon-company.png 2x" alt=""></span>'
			),
			"DATA_ID" => "mobile-grid-item-".$item["ID"]
		);
	}

	if (!empty($arCompanyIds))
	{
		//multi fields
		$dbFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::CompanyName,
				'ELEMENT_ID' => $arCompanyIds
			)
		);
		while ($arMulti = $dbFields->Fetch()) {
			if (!in_array($arMulti["TYPE_ID"], array(CCrmFieldMulti::PHONE, CCrmFieldMulti::EMAIL)))
				continue;

			if (isset($arResult['ITEMS'][$arMulti['ELEMENT_ID']]))
				$arResult['ITEMS'][$arMulti['ELEMENT_ID']]["FIELDS"][$arMulti['COMPLEX_ID']] = htmlspecialcharsbx($arMulti['VALUE']);

			$itemData[$arMulti['ELEMENT_ID']]["multi"][] = array(
				"name" => $arResult["FIELDS"][$arMulti['COMPLEX_ID']]["name"],
				"value" => htmlspecialcharsbx($arMulti['VALUE']),
				"type" => $arMulti["TYPE_ID"]
			);
		}

		foreach ($itemData as $id => $data) {
			$data["pageId"] = $arResult["pageId"];
			$data["entity"] = "company";
			$arResult['ITEMS'][$id]["ONCLICK"] = "BX.Mobile.Crm.EntityList.selectItem('" . htmlspecialcharsbx(CUtil::JSEscape($arParams["EVENT_NAME"])) . "', " . CUtil::PhpToJSObject($data) . ")";
		}
	}
}
//<-- COMPANY

// LEAD -->
if(CCrmOwnerType::LeadName == $effectiveEntityType)
{
	$sort = $leadSort;
	$select = $leadSelect;
	$filter = array();
	if(isset($_REQUEST["search"]))
	{
		CUtil::JSPostUnescape();
		$v = trim($_REQUEST["search"]);
		if (!empty($v))
		{
			$filter['%TITLE'] = $v;
			$filter['LOGIC'] = 'OR';
		}
	}

//fields to show
	$arResult["FIELDS"] = array(
		'COMPANY_TYPE' => array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMPANY_TYPE')),
	);
	$multiFields = CCrmMobileHelper::getFieldMultiInfo();

	foreach($multiFields as $code => $info)
	{
		if (
			CCrmFieldMulti::EMAIL == $code
			|| CCrmFieldMulti::PHONE == $code
		)
		{
			foreach($multiFields[$code] as $key => $field)
			{
				$arResult["FIELDS"][$code."_".$key] = array(
					"id" => $code."_".$key,
					"name" => $field["FULL"],
					//"type" => $code
				);
			}
		}
	}

	$dbRes = CCrmLead::GetListEx($sort, $filter, false, $navParams, $select);
	$dbRes->NavStart($navParams['nPageSize'], false);

	$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
	$arResult["NAV_PARAM"] = array(
		'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
		'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
		'PAGE_NAVNUM' => intval($dbRes->NavNum),
		'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
	);

	$itemData = array();
	$arLeadIds = array();
	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareLeadItem($item, $arParams);

		$arLeadIds[] = $item["ID"];

		$leadShowUrl = CComponentEngine::MakePathFromTemplate($arParams['LEAD_SHOW_URL_TEMPLATE'],
			array('lead_id' => $item['ID'])
		);
		$itemData[$item['ID']] = array(
			"id" => $item['ID'],
			"name" => $item['TITLE'],
			//"addTitle" => $item['COMPANY_TYPE'],
			"url" => $leadShowUrl,
			"entityType" => "lead"
		);

		$arResult['ITEMS'][$item['ID']] = array(
			"TITLE" => $item["TITLE"],
			"FIELDS" => $item,
			"DATA_ID" => "mobile-grid-item-".$item["ID"],
			"ICON_HTML" => '<span class="mobile-grid-field-title-icon"><img src="'.$this->getPath().'/images/icon-lead.png" srcset="'.$this->getPath().'/images/icon-lead.png 2x" alt=""></span>'
		);
	}

	if (!empty($arLeadIds))
	{
		//multi fields
		$dbFields = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::LeadName,
				'ELEMENT_ID' => $arLeadIds
			)
		);
		while ($arMulti = $dbFields->Fetch())
		{
			if (!in_array($arMulti["TYPE_ID"], array(CCrmFieldMulti::PHONE, CCrmFieldMulti::EMAIL)))
				continue;

			if (isset($arResult['ITEMS'][$arMulti['ELEMENT_ID']]))
				$arResult['ITEMS'][$arMulti['ELEMENT_ID']]["FIELDS"][$arMulti['COMPLEX_ID']] = htmlspecialcharsbx($arMulti['VALUE']);

			$itemData[$arMulti['ELEMENT_ID']]["multi"][] = array(
				"name" => $arResult["FIELDS"][$arMulti['COMPLEX_ID']]["name"],
				"value" => htmlspecialcharsbx($arMulti['VALUE']),
				"type" => $arMulti["TYPE_ID"]
			);
		}

		foreach ($itemData as $id => $data)
		{
			$data["pageId"] = $arResult["pageId"];
			$data["entity"] = "lead";
			$arResult['ITEMS'][$id]["ONCLICK"] = "BX.Mobile.Crm.EntityList.selectItem('" . htmlspecialcharsbx(CUtil::JSEscape($arParams["EVENT_NAME"])) . "', " . CUtil::PhpToJSObject($data) . ")";
		}
	}
}
//<-- LEAD

// DEAL -->
if(CCrmOwnerType::DealName == $effectiveEntityType)
{
	$sort = $dealSort;
	$select = $dealSelect;
	$filter = array();
	if(isset($_REQUEST["search"]))
	{
		CUtil::JSPostUnescape();
		$v = trim($_REQUEST["search"]);
		if (!empty($v))
		{
			$filter['%TITLE'] = $v;
			$filter['LOGIC'] = 'OR';
		}
	}

//fields to show
	$arResult["FIELDS"] = array(
		'COMPANY_TYPE' => array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_COMPANY_TYPE')),
	);

	$dbRes = CCrmDeal::GetListEx($sort, $filter, false, $navParams, $select);
	$dbRes->NavStart($navParams['nPageSize'], false);

	$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
	$arResult["NAV_PARAM"] = array(
		'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
		'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
		'PAGE_NAVNUM' => intval($dbRes->NavNum),
		'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
	);

	$itemData = array();
	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareDealItem($item, $arParams);

		$dealShowUrl = CComponentEngine::MakePathFromTemplate($arParams['DEAL_SHOW_URL_TEMPLATE'],
			array('deal_id' => $item['ID'])
		);
		$itemData[$item['ID']] = array(
			"id" => $item['ID'],
			"name" => $item['TITLE'],
			//"addTitle" => $item['COMPANY_TYPE'],
			"url" => $dealShowUrl,
			"entityType" => "deal"
		);

		$arResult['ITEMS'][$item['ID']] = array(
			"TITLE" => $item["TITLE"],
			"FIELDS" => $item,
			"DATA_ID" => "mobile-grid-item-".$item["ID"],
			"ICON_HTML" => '<span class="mobile-grid-field-title-icon"><img src="'.$this->getPath().'/images/icon-deal.png" srcset="'.$this->getPath().'/images/icon-deal.png 2x" alt=""></span>'
		);
	}

	foreach($itemData as $id => $data)
	{
		$data["pageId"] = $arResult["pageId"];
		$data["entity"] = "deal";
		$arResult['ITEMS'][$id]["ONCLICK"] = "BX.Mobile.Crm.EntityList.selectItem('".htmlspecialcharsbx(CUtil::JSEscape($arParams["EVENT_NAME"]))."', ".CUtil::PhpToJSObject($data).")";
	}
}
//<-- DEAL

// QUOTE -->
if(CCrmOwnerType::QuoteName == $effectiveEntityType)
{
	$sort = $quoteSort;
	$select = $quoteSelect;
	$filter = array();
	if(isset($_REQUEST["search"]))
	{
		CUtil::JSPostUnescape();
		$v = trim($_REQUEST["search"]);
		if (!empty($v))
		{
			$filter['%TITLE'] = $v;
			$filter['LOGIC'] = 'OR';
		}
	}

	$dbRes = CCrmQuote::GetList($sort, $filter, false, $navParams, $select);
	$dbRes->NavStart($navParams['nPageSize'], false);

	$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
	$arResult["NAV_PARAM"] = array(
		'PAGER_PARAM' => "PAGEN_{$arResult['PAGE_NAVNUM']}",
		'PAGE_NAVCOUNT' => intval($dbRes->NavPageCount),
		'PAGE_NAVNUM' => intval($dbRes->NavNum),
		'PAGE_NUMBER' => intval($dbRes->NavPageNomer)
	);

	$itemData = array();
	while($item = $dbRes->GetNext())
	{
		CCrmMobileHelper::PrepareQuoteItem($item, $arParams);

		$quoteShowUrl = CComponentEngine::MakePathFromTemplate($arParams['QUOTE_SHOW_URL_TEMPLATE'],
			array('quote_id' => $item['ID'])
		);
		$itemData[$item['ID']] = array(
			"id" => $item['ID'],
			"name" => $item['TITLE'],
			//"addTitle" => $item['COMPANY_TYPE'],
			"url" => $quoteShowUrl,
			"entityType" => "quote"
		);

		$arResult['ITEMS'][$item['ID']] = array(
			"TITLE" => $item["TITLE"],
			"FIELDS" => $item,
			"DATA_ID" => "mobile-grid-item-".$item["ID"],
			"ICON_HTML" => '<span class="mobile-grid-field-title-icon"><img src="'.$this->getPath().'/images/icon-quote.png" srcset="'.$this->getPath().'/images/icon-quote.png 2x" alt=""></span>'
		);
	}

	foreach($itemData as $id => $data)
	{
		$data["pageId"] = $arResult["pageId"];
		$data["entity"] = "quote";
		$arResult['ITEMS'][$id]["ONCLICK"] = "BX.Mobile.Crm.EntityList.selectItem('".htmlspecialcharsbx(CUtil::JSEscape($arParams["EVENT_NAME"]))."', ".CUtil::PhpToJSObject($data).")";
	}
}
//<-- QUOTE

$this->IncludeComponentTemplate();


