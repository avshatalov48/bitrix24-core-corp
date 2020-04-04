<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $APPLICATION;

$enablePaging = $arResult['ENABLE_PAGING'] = isset($_REQUEST['PAGING']) && strtoupper($_REQUEST['PAGING']) === 'Y';
$entityID = $arResult['ENTITY_ID'] = isset($_REQUEST['entity_id']) ? intval($_REQUEST['entity_id']) : 0;
if($entityID <= 0)
{
	ShowError(GetMessage('CRM_INVOICE_EVENT_LIST_ENTITY_ID_NOT_FOUND'));
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmAuthorizationHelper::CheckReadPermission('INVOICE', $entityID, $userPerms))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

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

$arParams['NAME_TEMPLATE'] = isset($arParams['NAME_TEMPLATE']) ? str_replace(array('#NOBR#', '#/NOBR#'), array('', ''), $arParams['NAME_TEMPLATE']) : CSite::GetNameFormat(false);
$arParams['UID'] = isset($arParams['UID']) ? $arParams['UID'] : '';
if(!isset($arParams['UID']) || $arParams['UID'] === '')
{
	$arParams['UID'] = 'mobile_crm_invoice_event_list';
}
$arResult['UID'] = $arParams['UID'];
$arResult['FILTER'] = array(
	array('id' => 'ORDER_ID')
);


$itemPerPage = isset($arParams['ITEM_PER_PAGE']) ? intval($arParams['ITEM_PER_PAGE']) : 0;
if($itemPerPage <= 0)
{
	$itemPerPage = 20;
}
$arParams['ITEM_PER_PAGE'] = $itemPerPage;

$arResult['EVENT_TYPES'] = CCrmInvoiceEvent::getTypes();

$sort = array('ID' => 'DESC');
$filter = array(
	'ORDER_ID' => $entityID,
	'TYPE' => array_keys($arResult['EVENT_TYPES'])
);

$navParams = array(
	'nPageSize' => $itemPerPage,
	'iNumPage' => $enablePaging ? false : 1,
	'bShowAll' => false
);

$select = array(
	'ID', 'TYPE', 'DATA',
	'DATE_CREATE', 'USER_ID'
);

$navigation = CDBResult::GetNavParams($navParams);
$CGridOptions = new CCrmGridOptions($arResult['UID']);
$navParams = $CGridOptions->GetNavParams($navParams);
$navParams['bShowAll'] = false;

$arResult['ITEMS'] = array();

$event = new CCrmInvoiceEvent();
$dbRes = $event->GetList($sort, $filter, false, $navParams, $select);
$dbRes->NavStart($navParams['nPageSize'], false);

$arResult['PAGE_NAVNUM'] = intval($dbRes->NavNum); // pager index
$arResult['PAGE_NUMBER'] = intval($dbRes->NavPageNomer); // current page index
$arResult['PAGE_NAVCOUNT'] = intval($dbRes->NavPageCount); // page count
$arResult['PAGER_PARAM'] = "PAGEN_{$arResult['PAGE_NAVNUM']}";
$arResult['PAGE_NEXT_NUMBER'] = $arResult['PAGE_NUMBER'] + 1;

$items = array();
$userBindings = array();
while($item = $dbRes->Fetch())
{
	$itemKey = isset($item['ID']) ? $item['ID'] : '';

	$userKey = isset($item['USER_ID']) ? $item['USER_ID'] : '';
	if($userKey !== '')
	{
		if(!isset($userBindings[$userKey]))
		{
			$userBindings[$userKey] = array();
		}
		$userBindings[$userKey][] = $itemKey;
	}

	$items[$itemKey] = &$item;
	unset($item);
}

if(!empty($userBindings))
{
	$userEnity = new CUser();
	$by = 'ID'; $order = 'ASC';
	$dbUsers = $userEnity->GetList(
		$by,
		$order,
		array('ID' => implode('|', array_keys($userBindings))),
		array('SELECT' => array('ID', 'LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME'))
	);
	if($dbUsers)
	{
		while ($user = $dbUsers->Fetch())
		{
			$userID = $user['ID'];
			if(!isset($userBindings[$userID]))
			{
				continue;
			}

			foreach($userBindings[$userID] as $itemKey)
			{
				if(isset($items[$itemKey]))
				{
					$item = &$items[$itemKey];
					$item['USER_LOGIN'] = $user['LOGIN'];
					$item['USER_NAME'] = $user['NAME'];
					$item['USER_LAST_NAME'] = $user['LAST_NAME'];
					$item['USER_SECOND_NAME'] = $user['SECOND_NAME'];
					unset($item);
				}
			}
		}
	}
}

$enums = array('EVENT_TYPES' => $arResult['EVENT_TYPES']);
foreach($items as &$item)
{
	CCrmMobileHelper::PrepareInvoiceEventItem($item, $arParams, $event, $enums);
}
unset($item);

$arResult['ITEMS'] = array_values($items);
unset($items);

//NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL -->
if($arResult['PAGE_NEXT_NUMBER'] > $arResult['PAGE_NAVCOUNT'])
{
	$arResult['NEXT_PAGE_URL'] = '';
}
else
{
	$arResult['NEXT_PAGE_URL'] = $APPLICATION->GetCurPageParam(
		'AJAX_CALL=Y&PAGING=Y&FORMAT=json&'.$arResult['PAGER_PARAM'].'='.$arResult['PAGE_NEXT_NUMBER'],
		array('AJAX_CALL', 'PAGING', 'FORMAT', $arResult['PAGER_PARAM'])
	);
}

$arResult['SERVICE_URL'] = '';
//<-- NEXT_PAGE_URL, SEARCH_PAGE_URL, SERVICE_URL

$format = isset($_REQUEST['FORMAT']) ? strtolower($_REQUEST['FORMAT']) : '';
// Only JSON format is supported
if($format !== '' && $format !== 'json')
{
	$format = '';
}
$this->IncludeComponentTemplate($format);

