<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;
$arResult['RUBRIC'] = array('ENABLED' => false);

$enablePaging = $arResult['ENABLE_PAGING'] = isset($_GET['PAGING']) && mb_strtoupper($_GET['PAGING']) === 'Y';
$entityTypeID = $arResult['ENTITY_TYPE_ID'] = isset($_GET['entity_type_id']) ? intval($_GET['entity_type_id']) : 0;
$entityID = $arResult['ENTITY_ID'] = isset($_GET['entity_id']) ? intval($_GET['entity_id']) : 0;

$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);

$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_event_list';
}
$arResult['UID'] = $arParams['UID'];

$arResult['FILTER'] = array(
	array('id' => 'ENTITY_TYPE'),
	array('id' => 'ENTITY_ID'),
	array('id' => 'EVENT_TYPE'),
);

$arResult['FILTER_PRESETS'] = array(
	'filter_custom' => array('name' => GetMessage('M_CRM_EVENT_LIST_PRESET_CUSTOM'), 'fields' => array('EVENT_TYPE' => '0')),
);

$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$sort = array('EVENT_REL_ID' => 'DESC');
$filter = array();
$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);
$select = array(
	'ID', 'EVENT_NAME',
	'EVENT_TEXT_1', 'EVENT_TEXT_2',
	'DATE_CREATE',
	'CREATED_BY_ID', 'CREATED_BY_LOGIN', 'CREATED_BY_NAME', 'CREATED_BY_SECOND_NAME', 'CREATED_BY_LAST_NAME'
);

$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CCrmGridOptions($arResult['UID']);
$navParams = $CGridOptions->GetNavParams($navParams);
$navParams['bShowAll'] = false;

$filter = $CGridOptions->GetFilter($arResult['FILTER']);
$arResult['GRID_FILTER_APPLIED'] = isset($filter['GRID_FILTER_APPLIED']) && $filter['GRID_FILTER_APPLIED'];
if($arResult['GRID_FILTER_APPLIED'])
{
	$filterID = $arResult['GRID_FILTER_ID'] = isset($filter['GRID_FILTER_ID']) ? $filter['GRID_FILTER_ID'] : '';
	$arResult['GRID_FILTER_NAME'] = isset($arResult['FILTER_PRESETS'][$filterID]) ? $arResult['FILTER_PRESETS'][$filterID]['name'] : '';
}
else
{
	$arResult['GRID_FILTER_ID'] = '';
	$arResult['GRID_FILTER_NAME'] = '';
}

if($entityTypeID > 0 && $entityID > 0)
{
	$arResult['RUBRIC']['ENABLED'] = true;

	$filter['ENTITY_TYPE'] = CCrmOwnerType::ResolveName($entityTypeID);
	$filter['ENTITY_ID'] = $entityID;

	$arResult['RUBRIC']['TITLE'] = CCrmOwnerType::GetCaption($entityTypeID, $entityID);
	$arResult['RUBRIC']['FILTER_PRESETS'] = array('clear_filter', 'filter_custom');
}

$arResult['ITEMS'] = array();

if ($entityTypeID == CCrmOwnerType::Invoice)
{
	$arFilter = array();
	$arFilter['ORDER_ID'] = $entityID;

	$arResult['EVENT_TYPES'] = CCrmInvoiceEvent::getTypes();

	$event = new CCrmInvoiceEvent();
	if (!array_key_exists('TYPE', $arFilter))
		$arFilter['TYPE'] = array_keys($arResult['EVENT_TYPES']);
	$dbRes = $event->GetList(array('date_create' => 'desc'), $arFilter, false, $arNavParams, array());

}
else
{
	$dbRes = CCrmEvent::GetListEx($sort, $filter, false, $arNavParams, $select, array());
	$dbRes->NavStart($navParams['nPageSize'], false);
}

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult['PAGE_NUMBER'] = intval($dbRes->NavPageNomer); // current page index
$arResult['PAGE_NAVCOUNT'] = intval($dbRes->NavPageCount); // page count
$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;

$arUserDistinct = array();
while($item = $dbRes->Fetch())
{
	if ($entityTypeID == CCrmOwnerType::Invoice)
	{
		$item['~EVENT_NAME'] = $arResult['EVENT_TYPES'][$item['TYPE']];
		$item['EVENT_NAME'] = htmlspecialcharsbx($item['~EVENT_NAME']);

		$arEventDescr = $event->GetRecordDescription($item['TYPE'], $item['DATA']);
		if ($arEventDescr)
		{
			$item['EVENT_INFO'] = strip_tags($arEventDescr['INFO'], '<br>');

			if (mb_strlen($item['EVENT_INFO']) > 255)
			{
				$item['EVENT_TEXT_1'] = '<div id="event_desc_short_'.$item['ID'].'">'.mb_substr(($item['EVENT_INFO']), 0, 252).'...</div>';
			}
			else
				$item['EVENT_TEXT_1'] = !empty($item['EVENT_INFO'])? ($item['EVENT_INFO']): '';

			$item['EVENT_TEXT_1'] = nl2br($item['EVENT_TEXT_1']);
		}

		$arUserDistinct[intval($item['USER_ID'])] = true;
	}

	CCrmMobileHelper::PrepareEventItem($item, $arParams);

	$arResult['ITEMS'][] = &$item;
	unset($item);
}

// for invoice - get users info -------
$arUserDistinct = array_keys($arUserDistinct);
$nUsers = count($arUserDistinct);
if ($nUsers > 0 && !($nUsers === 1 && $arUserDistinct[0] == 0))
{
	$users = new CUser();
	$dbResUsers = $users->GetList(
		'ID', 'ASC',
		array('ID' => implode('|', $arUserDistinct)),
		array('SELECT' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME'))
	);
	if ($dbResUsers)
	{
		while ($arUser = $dbResUsers->Fetch())
		{
			$arUserInfo[$arUser['ID']] = array();
			$arUserInfo[$arUser['ID']]['ID'] = $arUser['ID'];
			$arUserInfo[$arUser['ID']]['LOGIN'] = $arUser['LOGIN'];
			$arUserInfo[$arUser['ID']]['NAME'] = $arUser['NAME'];
			$arUserInfo[$arUser['ID']]['LAST_NAME'] = $arUser['LAST_NAME'];
			$arUserInfo[$arUser['ID']]['SECOND_NAME'] = $arUser['SECOND_NAME'];
		}
		unset($dbResUsers);
	}
	unset($users);
}
unset($arUserDistinct, $nUsers);

// fill user fields
foreach ($arResult['ITEMS'] as &$eventFields)
{
	$eventFields['~USER_ID'] = $eventFields['USER_ID'];
	$eventFields['USER_ID'] = htmlspecialcharsbx($eventFields['~USER_ID']);
	$eventFields['CREATED_BY_LOGIN'] = $eventFields['~CREATED_BY_LOGIN'] = '';
	$eventFields['CREATED_BY_NAME'] = $eventFields['~CREATED_BY_NAME'] = '';
	$eventFields['CREATED_BY_LAST_NAME'] = $eventFields['~CREATED_BY_LAST_NAME'] = '';
	$eventFields['CREATED_BY_SECOND_NAME'] = $eventFields['~CREATED_BY_SECOND_NAME'] = '';
	$userId = intval($eventFields['~USER_ID']);
	if (isset($arUserInfo[$userId]))
	{
		$eventFields['~CREATED_BY_LOGIN'] = $arUserInfo[$userId]['LOGIN'];
		$eventFields['CREATED_BY_LOGIN'] = htmlspecialcharsbx($eventFields['~CREATED_BY_LOGIN']);
		$eventFields['~CREATED_BY_NAME'] = $arUserInfo[$userId]['NAME'];
		$eventFields['CREATED_BY_NAME'] = htmlspecialcharsbx($eventFields['~CREATED_BY_NAME']);
		$eventFields['~CREATED_BY_LAST_NAME'] = $arUserInfo[$userId]['LAST_NAME'];
		$eventFields['CREATED_BY_LAST_NAME'] = htmlspecialcharsbx($eventFields['~CREATED_BY_LAST_NAME']);
		$eventFields['~CREATED_BY_SECOND_NAME'] = $arUserInfo[$userId]['SECOND_NAME'];
		$eventFields['CREATED_BY_SECOND_NAME'] = htmlspecialcharsbx($eventFields['~CREATED_BY_SECOND_NAME']);
	}

	$eventFields['~CREATED_BY_FULL_NAME'] = CUser::FormatName(
		$arParams["NAME_TEMPLATE"],
		array(
			'LOGIN' => $eventFields['CREATED_BY_LOGIN'],
			'NAME' => $eventFields['CREATED_BY_NAME'],
			'LAST_NAME' => $eventFields['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $eventFields['CREATED_BY_SECOND_NAME']
		),
		true, false
	);
	$eventFields['CREATED_BY_FORMATTED_NAME'] = $eventFields['~CREATED_BY_FULL_NAME'];
}
// ---- for invoice

//NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL -->
if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['NEXT_PAGE_URL'] = '';
}
elseif($contactID > 0)
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&contact_id='.$contactID.'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'contact_id', 'company_id', $arResult['PAGER_PARAM'])
	);
}
elseif($companyID > 0)
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&company_id='.$companyID.'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', 'contact_id', 'company_id', $arResult['PAGER_PARAM'])
	);
}
else
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&SEARCH='.($enableSearch ? 'Y' : 'N').'&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', 'SEARCH', $arResult['PAGER_PARAM'])
	);
}

$arResult['SEARCH_PAGE_URL'] = $APPLICATION->GetCurPageParam(
	'AJAX_CALL=Y&SEARCH=Y&FORMAT=json&apply_filter=Y&save=Y',
	array('AJAX_CALL', 'SEARCH', 'FORMAT', 'save', 'apply_filter', 'clear_filter')
);
$arResult['SERVICE_URL'] = SITE_DIR.'bitrix/components/bitrix/mobile.crm.event.list/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get();
//<-- NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL
$arResult['IS_FILTERED'] = !empty($filter);

$format = isset($_REQUEST['FORMAT'])? mb_strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);

