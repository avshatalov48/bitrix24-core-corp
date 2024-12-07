<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm\Restriction\RestrictionManager;

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

if(!RestrictionManager::isHistoryViewPermitted())
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

$arParams['PATH_TO_EVENT_LIST'] = CrmCheckPath('PATH_TO_EVENT_LIST', $arParams['PATH_TO_EVENT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');

//$arResult['EVENT_ENTITY_LINK'] = isset($arParams['EVENT_ENTITY_LINK']) && $arParams['EVENT_ENTITY_LINK'] == 'Y'? 'Y': 'N';
$arResult['EVENT_HINT_MESSAGE'] = isset($arParams['EVENT_HINT_MESSAGE']) && $arParams['EVENT_HINT_MESSAGE'] == 'N'? 'N': 'Y';
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['INTERNAL'] = isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y';
if(isset($arParams['ENABLE_CONTROL_PANEL']))
{
	$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']);
}
else
{
	$arResult['ENABLE_CONTROL_PANEL'] = !(isset($arParams['INTERNAL']) && $arParams['INTERNAL'] === 'Y');
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$bInternal = false;
if ($arParams['INTERNAL'] == 'Y' || $arParams['GADGET'] == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
$arResult['INTERNAL_EDIT'] = false;
if ($arParams['INTERNAL_EDIT'] == 'Y')
	$arResult['INTERNAL_EDIT'] = true;
$arResult['GADGET'] =  isset($arParams['GADGET']) && $arParams['GADGET'] == 'Y'? 'Y': 'N';

$arFilter = array();
if (isset($arParams['ENTITY_TYPE']) && !empty($arParams['ENTITY_TYPE']))
{
	/*$arFilter['ENTITY_TYPE'] = */$arResult['ENTITY_TYPE'] = $arParams['ENTITY_TYPE'];
}
if (isset($arParams['ENTITY_ID']))
{
	$arParams['ORDER_ID'] = &$arParams['ENTITY_ID'];
	unset($arParams['ENTITY_ID']);
}
if (isset($arParams['ORDER_ID']) && is_array($arParams['ORDER_ID']))
{
	array_walk(
		$arParams['ORDER_ID'],
		function (&$v) {
			$v = (int)$v;
		}
	);
	$arFilter['ORDER_ID'] = $arResult['ORDER_ID'] = $arParams['ORDER_ID'];
}
elseif (isset($arParams['ORDER_ID']) && intval($arParams['ORDER_ID']) > 0)
{
	$arFilter['ORDER_ID'] = $arResult['ORDER_ID'] = intval($arParams['ORDER_ID']);
}
if(isset($arParams['EVENT_COUNT']))
	$arResult['EVENT_COUNT'] = intval($arParams['EVENT_COUNT']) > 0? intval($arParams['EVENT_COUNT']): 50;
else
	$arResult['EVENT_COUNT'] = 50;

$arResult['PREFIX'] = isset($arParams['PREFIX']) ? strval($arParams['PREFIX']) : '';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
$arResult['VIEW_ID'] = isset($arParams['VIEW_ID']) ? $arParams['VIEW_ID'] : '';

$filterFieldPrefix = $bInternal ? "{$arResult['TAB_ID']}_{$arResult['VIEW_ID']}" : '';
if($bInternal)
{
	$filterFieldPrefix = mb_strtoupper($arResult['TAB_ID']).'_'.mb_strtoupper($arResult['VIEW_ID']).'_';
}

$arResult['FILTER_FIELD_PREFIX'] = $filterFieldPrefix;

$tabParamName = $arResult['FORM_ID'] !== '' ? $arResult['FORM_ID'].'_active_tab' : 'active_tab';
$activeTabID = isset($_REQUEST[$tabParamName]) ? $_REQUEST[$tabParamName] : '';

if($arResult['VIEW_ID'] <> '')
{
	$arResult['GRID_ID'] = $arResult['INTERNAL']? 'CRM_INTERNAL_INVOICE_EVENTS_'.$arResult['TAB_ID'].'_'.$arResult['VIEW_ID'] : 'CRM_INVOICE_EVENTS';
}
else
{
	$arResult['GRID_ID'] = $arResult['INTERNAL']? 'CRM_INTERNAL_INVOICE_EVENTS_'.$arResult['TAB_ID'] : 'CRM_INVOICE_EVENTS';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST'
	&& check_bitrix_sessid()
	&& isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	if ($_POST['action_button_'.$arResult['GRID_ID']] == 'delete' && isset($_POST['ID']) && is_array($_POST['ID']))
	{
		foreach($_POST['ID'] as $ID)
		{
			\Bitrix\Crm\Invoice\Internals\InvoiceChangeTable::delete((int)$ID);
		}
		unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_POST['AJAX_CALL']))
		LocalRedirect('?'.$arParams['FORM_ID'].'_active_tab=tab_event');
}
else if ($_SERVER['REQUEST_METHOD'] == 'GET'
	&& check_bitrix_sessid()
	&& isset($_GET['action_'.$arResult['GRID_ID']]))
{
	if ($_GET['action_'.$arResult['GRID_ID']] == 'delete')
	{
		\Bitrix\Crm\Invoice\Internals\InvoiceChangeTable::delete((int)$_GET['ID']);
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_GET['AJAX_CALL']))
		LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab='.$arResult['TAB_ID'] : '');
}

$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array('EVENT_DESC');
$arResult['FILTER_PRESETS'] = array();

$arResult['EVENT_TYPES'] = CCrmInvoiceEvent::getTypes();
$eventTypeListItems = array('' => '') + $arResult['EVENT_TYPES'];

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('EVENT_DESC');

	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => 'ID', 'default' => false),
	);

	$enabledEntityTypeNames = array();
	$currentUserPerms = CCrmPerms::GetCurrentUserPermissions();
	$arResult['FILTER'] = array_merge(
		$arResult['FILTER'],
		array(
//			array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'default' => true, 'type' => 'list', 'items' => $arEntityType),
			array('id' => 'TYPE', 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => $eventTypeListItems),
//			array('id' => 'EVENT_ID', 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE')),
//			array('id' => 'EVENT_DESC', 'name' => GetMessage('CRM_COLUMN_EVENT_DESC')),
			array('id' => 'USER_ID',  'name' => GetMessage('CRM_COLUMN_CREATED_BY_ID'), 'default' => true, 'enable_settings' => false, 'type' => 'user'),
			array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'default' => true, 'type' => 'date')
		)
	);

	$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CREATE_TODAY'), 'fields' => array('DATE_CREATE_datesel' => 'today')),
		'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CREATE_YESTERDAY'), 'fields' => array('DATE_CREATE_datesel' => 'yesterday')),
		'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CREATE_MY'), 'fields' => array( 'USER_ID' => $currentUserID, 'USER_ID_name' => $currentUserName))
	);
}
elseif(isset($arParams['SHOW_INTERNAL_FILTER']) && mb_strtoupper(strval($arParams['SHOW_INTERNAL_FILTER'])) === 'Y')
{
	$arResult['FILTER'] = array(
		array('id' => "{$filterFieldPrefix}ID", 'name' => 'ID', 'default' => false),
		array('id' => "{$filterFieldPrefix}TYPE", 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => $eventTypeListItems),
	//	array('id' => "{$filterFieldPrefix}EVENT_ID", 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'default' => true, 'type' => 'list', 'items' => array('' => '') + CCrmStatus::GetStatusList('EVENT_TYPE')),
	//	array('id' => "{$filterFieldPrefix}EVENT_DESC", 'name' => GetMessage('CRM_COLUMN_EVENT_DESC')),
		array('id' => "{$filterFieldPrefix}USER_ID",  'name' => GetMessage('CRM_COLUMN_CREATED_BY_ID'), 'default' => true, 'enable_settings' => false, 'type' => 'user'),
		array('id' => "{$filterFieldPrefix}DATE_CREATE", 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'default' => true, 'type' => 'date'),
	);
}

$arResult['HEADERS'] = array();
$arResult['HEADERS'][] = array('id' => 'ID', 'name' => 'ID', 'sort' => 'id', 'default' => false, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'default' => true, 'editable' => false);
//	if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
//	{
//		$arResult['HEADERS'][] = array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'sort' => false, 'default' => true, 'editable' => false);
//		$arResult['HEADERS'][] = array('id' => 'ENTITY_TITLE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TITLE'), 'sort' => false, 'default' => true, 'editable' => false);
//	}
$arResult['HEADERS'][] = array('id' => 'CREATED_BY_FULL_NAME', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => false, 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_NAME', 'name' => GetMessage('CRM_COLUMN_EVENT_NAME'), 'sort' => false, 'default' => true, 'editable' => false);
$arResult['HEADERS'][] = array('id' => 'EVENT_DESC', 'name' => GetMessage('CRM_COLUMN_EVENT_DESC'), 'sort' => false, 'default' => true, 'editable' => false);

$arNavParams = array(
	'nPageSize' => $arResult['EVENT_COUNT']
);

$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);

if (($arResult['TAB_ID'] === '' || $arResult['TAB_ID'] === $activeTabID)
	&& isset($_REQUEST['clear_filter']) && $_REQUEST['clear_filter'] == 'Y')
{
	$urlParams = array();
	foreach($arResult['FILTER'] as $arFilterField)
	{
		$filterFieldID = $arFilterField['id'];
		if ($arFilterField['type'] == 'user')
		{
			$urlParams[] = $filterFieldID.'_name';
		}
		if ($arFilterField['type'] == 'date')
		{
			$urlParams[] = $filterFieldID.'_datesel';
			$urlParams[] = $filterFieldID.'_days';
			$urlParams[] = $filterFieldID.'_from';
			$urlParams[] = $filterFieldID.'_to';
		}

		$urlParams[] = $filterFieldID;
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	if($arResult['TAB_ID'] !== '')
	{
		$urlParams[] = $tabParamName;
		LocalRedirect($APPLICATION->GetCurPageParam(
			urlencode($tabParamName).'='.urlencode($arResult['TAB_ID']),
			$urlParams));
	}
	else
	{
		LocalRedirect($APPLICATION->GetCurPageParam('',$urlParams));
	}
}

$arGridFilter = $CGridOptions->GetFilter($arResult['FILTER']);

$prefixLength = mb_strlen($filterFieldPrefix);

if($prefixLength == 0)
{
	$arFilter = array_merge($arFilter, $arGridFilter);
}
else
{
	foreach($arGridFilter as $key=>&$value)
	{
		$arFilter[mb_substr($key, $prefixLength)] = $value;
	}
}
unset($value);

foreach ($arFilter as $k => $v)
{
	$arMatch = array();
	if (preg_match('/(.*)_from$/iu', $k, $arMatch))
	{
		$arFilter['>='.$arMatch[1]] = $v;
		unset($arFilter[$k]);
	}
	else if (preg_match('/(.*)_to$/iu', $k, $arMatch))
	{
		if ($arMatch[1] == 'DATE_CREATE' && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/u', $v))
			$v = CCrmDateTimeHelper::SetMaxDayTime($v);

		$arFilter['<='.$arMatch[1]] = $v;
		unset($arFilter[$k]);
	}
	else if (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	else if ($k == 'USER_ID')
	{
		// For suppress comparison by LIKE
		$arFilter['=USER_ID'] = $v;
	}
}

$_arSort = $CGridOptions->GetSorting(array(
	'sort' => array('date_create' => 'desc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;
$arSelect = $CGridOptions->GetVisibleColumns();
//   // HACK: ignore entity related fields if entity info is not displayed
//	if ($arResult['EVENT_ENTITY_LINK'] !== 'Y')
//	{
//		$key = array_search('ENTITY_TYPE', $arSelect, true);
//		if($key !== false)
//		{
//			unset($arSelect[$key]);
//		}

		$key = array_search('ENTITY_TITLE', $arSelect, true);
		if($key !== false)
		{
			unset($arSelect[$key]);
		}
//	}
$CGridOptions->SetVisibleColumns($arSelect);

$nTopCount = false;
if ($arResult['GADGET'] == 'Y')
{
	$nTopCount = $arResult['EVENT_COUNT'];
}

if($nTopCount > 0)
{
	$arNavParams['nTopCount'] = $nTopCount;
}

//$arEntityList = Array();
$arResult['EVENT'] = Array();

$event = new CCrmInvoiceEvent();
if (!array_key_exists('TYPE', $arFilter))
	$arFilter['TYPE'] = array_keys($arResult['EVENT_TYPES']);
$obRes = $event->GetList($arResult['SORT'], $arFilter, false, $arNavParams, array());

$arResult['DB_LIST'] = $obRes;
$arResult['ROWS_COUNT'] = $obRes->NavRecordCount;
// Prepare raw filter ('=CREATED_BY' => 'CREATED_BY')
$arResult['DB_FILTER'] = array();
foreach($arFilter as $filterKey => &$filterItem)
{
	$info = CSqlUtil::GetFilterOperation($filterKey);
	$arResult['DB_FILTER'][$info['FIELD']] = $filterItem;
}
unset($filterItem);

$arUserDistinct = array();
$arUserInfo = array();
$arEventDescr = array();
while ($arEvent = $obRes->Fetch())
{
	$arEvent['PATH_TO_EVENT_DELETE'] =  CHTTP::urlAddParams($arParams['PATH_TO_EVENT_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arEvent['ID'], 'sessid' => bitrix_sessid()));
	$arEvent['~FILES'] = $arEvent['FILES'];
	//$arEvent['~EVENT_NAME'] = $arEvent['EVENT_NAME'];
	$arUserDistinct[intval($arEvent['USER_ID'])] = true;
	//$arEvent['EVENT_NAME'] = htmlspecialcharsbx($arEvent['~EVENT_NAME']);

	if (!empty($arEvent['FILES']))
	{
		$i=1;
		$arFiles = array();
		$arFilter = array(
			'@ID' => implode(',', $arEvent['FILES'])
		);
		$rsFile = CFile::GetList(array(), $arFilter);
		while($arFile = $rsFile->Fetch())
		{
			$arFiles[$i++] = array(
				'NAME' => $arFile['ORIGINAL_NAME'],
				'PATH' => CComponentEngine::MakePathFromTemplate(
					'/bitrix/components/bitrix/crm.event.view/show_file.php?eventId=#event_id#&fileId=#file_id#',
					array('event_id' => $arEvent['ID'], 'file_id' => $arFile['ID'])
				),
				'SIZE' => CFile::FormatSize($arFile['FILE_SIZE'], 1)
			);
		}
		$arEvent['FILES'] = $arFiles;
	}
	//$arEntityList[$arEvent['ENTITY_TYPE']][$arEvent['ENTITY_ID']] = $arEvent['ENTITY_ID'];

	$arEvent['~EVENT_NAME'] = $arResult['EVENT_TYPES'][$arEvent['TYPE']];
	$arEvent['EVENT_NAME'] = htmlspecialcharsbx($arEvent['~EVENT_NAME']);
	$arEventDescr = $event->GetRecordDescription($arEvent['TYPE'], $arEvent['DATA']);
	if ($arEventDescr)
	{
		$arEvent['EVENT_INFO'] = strip_tags($arEventDescr['INFO'], '<br>');

		if (mb_strlen($arEvent['EVENT_INFO']) > 255)
		{
			$arEvent['EVENT_DESC'] = '<div id="event_desc_short_'.$arEvent['ID'].'">'.mb_substr(($arEvent['EVENT_INFO']), 0, 252).'... <a href="#more" onclick="crm_event_desc('.$arEvent['ID'].')">'.GetMessage('CRM_EVENT_DESC_MORE').'</a></div>';
			$arEvent['EVENT_DESC'] .= '<div id="event_desc_full_'.$arEvent['ID'].'" style="display: none">'.($arEvent['EVENT_INFO']).'</div>';
		}
		else
			$arEvent['EVENT_DESC'] = !empty($arEvent['EVENT_INFO'])? ($arEvent['EVENT_INFO']): '';
		$arEvent['EVENT_DESC'] = nl2br($arEvent['EVENT_DESC']);
	}

	$arResult['EVENT'][] = $arEvent;
}
unset($arEventDescr);

// get users info
$arUserDistinct = array_keys($arUserDistinct);
$nUsers = count($arUserDistinct);
if ($nUsers > 0 && !($nUsers === 1 && $arUserDistinct[0] == 0))
{
	$users = new CUser();
	$dbResUsers = $users->GetList(
		'ID',
		'ASC',
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
foreach ($arResult['EVENT'] as &$eventFields)
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
	$eventFields['CREATED_BY_FULL_NAME'] = $eventFields['~CREATED_BY_FULL_NAME'];
	$eventFields['CREATED_BY_LINK'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'], array('user_id' => $eventFields['USER_ID']));
}
unset($userId, $arUserInfo);

//	if ($arResult['EVENT_ENTITY_LINK'] == 'Y')
//	{
//		if (isset($arEntityList['LEAD']) && !empty($arEntityList['LEAD']))
//		{
//			$dbRes = CCrmLead::GetList(Array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => $arEntityList['LEAD']));
//			while ($arRes = $dbRes->Fetch())
//			{
//				$arEntityList['LEAD'][$arRes['ID']] = Array(
//					'ENTITY_TITLE' => $arRes['TITLE'],
//					'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'], array('lead_id' => $arRes['ID']))
//				);
//			}
//		}
//		if (isset($arEntityList['CONTACT']) && !empty($arEntityList['CONTACT']))
//		{
//			$dbRes = CCrmContact::GetList(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('ID' => $arEntityList['CONTACT']));
//			while ($arRes = $dbRes->Fetch())
//			{
//				$arEntityList['CONTACT'][$arRes['ID']] = Array(
//					'ENTITY_TITLE' => $arRes['LAST_NAME'].' '.$arRes['NAME'],
//					'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $arRes['ID']))
//				);
//			}
//		}
//		if (isset($arEntityList['COMPANY']) && !empty($arEntityList['COMPANY']))
//		{
//			$dbRes = CCrmCompany::GetList(Array('TITLE'=>'ASC'), array('ID' => $arEntityList['COMPANY']));
//			while ($arRes = $dbRes->Fetch())
//			{
//				$arEntityList['COMPANY'][$arRes['ID']] = Array(
//					'ENTITY_TITLE' => $arRes['TITLE'],
//					'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $arRes['ID']))
//				);
//			}
//		}
//		if (isset($arEntityList['DEAL']) && !empty($arEntityList['DEAL']))
//		{
//			$dbRes = CCrmDeal::GetList(Array('TITLE'=>'ASC'), array('ID' => $arEntityList['DEAL']));
//			while ($arRes = $dbRes->Fetch())
//			{
//				$arEntityList['DEAL'][$arRes['ID']] = Array(
//					'ENTITY_TITLE' => $arRes['TITLE'],
//					'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $arRes['ID']))
//				);
//			}
//		}
//
//		foreach($arResult['EVENT'] as $key => $ar)
//		{
//			$arResult['EVENT'][$key]['ENTITY_TITLE'] = htmlspecialcharsbx($arEntityList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_TITLE']);
//			$arResult['EVENT'][$key]['ENTITY_LINK'] = $arEntityList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_LINK'];
//		}
//	}

$this->IncludeComponentTemplate();

return $obRes->SelectedRowsCount();

?>
