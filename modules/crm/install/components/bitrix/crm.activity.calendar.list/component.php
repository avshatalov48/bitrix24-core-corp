<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule(CRM_MODULE_CALENDAR_ID))
{
	ShowError(GetMessage('CALENDAR_MODULE_NOT_INSTALLED'));
	return 0;
}

if (!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}


$arParams['PATH_TO_TASK_LIST'] = CrmCheckPath('PATH_TO_TASK_LIST', $arParams['PATH_TO_TASK_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$arResult['ACTIVITY_ENTITY_LINK'] = isset($arParams['ACTIVITY_ENTITY_LINK']) && $arParams['ACTIVITY_ENTITY_LINK'] == 'Y'? 'Y': 'N';

CUtil::InitJSCore(array('ajax', 'tooltip'));

$CCrmActivity = new CCrmActivityTask();

$arParams['GRID_ID_SUFFIX'] = '';
$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && strlen($arParams['GADGET_ID']) > 0)
	$arResult['GADGET'] = 'Y';

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

if (!empty($arParams['INTERNAL_FILTER']) || $arResult['GADGET'] == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? $this->GetParent()->GetName() : '';
	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
	$arSort = $arParams['INTERNAL_SORT'];

$sShortEntity = '';
$iEntityID = 0;
if (isset($arFilter['ENTITY_TYPE']))
	$sShortEntity = CUserTypeCrm::GetShortEntityType($arFilter['ENTITY_TYPE']);
if (isset($arFilter['ENTITY_ID']))
{
	$iEntityID = (int)$arFilter['ENTITY_ID'];
	if ($iEntityID > 0 && !empty($sShortEntity))
		$sShortEntity = CUserTypeCrm::GetShortEntityType('LEAD');
}

if (empty($arParams['ACTIVITY_CALENDAR_COUNT']))
	$arParams['ACTIVITY_CALENDAR_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['ACTIVITY_CALENDAR_COUNT']
);
$arNavigation = CDBResult::GetNavParams($arNavParams);

global $APPLICATION;

$arResult['GRID_ID'] = 'CRM_ACTIVITY_CALENDAR_LIST'.($bInternal ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['FILTER'] = array();
$arResult['FILTER_PRESETS'] = array();

if (!$bInternal)
{
	$arEntityType = Array(
		'' => '',
		'LEAD' => GetMessage('CRM_ENTITY_TYPE_LEAD'),
		'CONTACT' => GetMessage('CRM_ENTITY_TYPE_CONTACT'),
		'COMPANY' => GetMessage('CRM_ENTITY_TYPE_COMPANY'),
		'DEAL' => GetMessage('CRM_ENTITY_TYPE_DEAL')
	);

	ob_start();
	$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => Array('LEAD', 'CONTACT', 'COMPANY', 'DEAL'),
			'INPUT_NAME' => 'UF_CRM_CAL_EVENT',
			'INPUT_VALUE' => isset($_REQUEST['UF_CRM_CAL_EVENT']) ? $_REQUEST['UF_CRM_CAL_EVENT'] : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true,
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arResult['FILTER'] = array(
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'UF_CRM_CAL_EVENT', 'name' => GetMessage('CRM_COLUMN_UF_CRM_CAL_EVENT'), 'type' => 'custom', 'value' => $sVal),
		array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'type' => 'list', 'items' => $arEntityType),
		array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'default' => 'Y'),
		array('id' => 'DT_FROM', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_DATE_START'), 'type' => 'date'),
		array('id' => 'DT_TO', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_DATE_END'), 'type' => 'date'),
		array('id' => 'DESCRIPTION', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_DESCRIPTION'), 'type' => 'string'),
		array('id' => 'OWNER_ID',  'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'default' => false, 'enable_settings' => false, 'type' => 'user')
	);

	$arResult['FILTER_PRESETS'] = array();
}

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'editable' => false, 'type' => 'int'),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => true, 'editable' => false, 'type' => 'string')
);
if ($arResult['ACTIVITY_ENTITY_LINK'] == 'Y')
{
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'sort' => false, 'default' => true, 'editable' => false);
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TITLE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TITLE'), 'sort' => false, 'default' => true, 'editable' => false);
}
$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'DT_FROM', 'name' => GetMessage('CRM_COLUMN_DATE_START'), 'sort' => 'date_end', 'default' => true, 'editable' => false, 'type' => 'date'),
	array('id' => 'DT_TO', 'name' => GetMessage('CRM_COLUMN_DATE_END'), 'sort' => 'date_to', 'default' => true, 'editable' => false, 'type' => 'date'),
	array('id' => 'DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_DESCRIPTION'), 'sort' => 'description', 'default' => false, 'editable' => false, 'type' => 'string'),
	array('id' => 'OWNER_ID', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'responsible_id', 'default' => true, 'editable' => false)
));


if ($_SERVER['REQUEST_METHOD'] == 'GET'
	&& check_bitrix_sessid()
	&& isset($_GET['action_'.$arResult['GRID_ID']]))
{
	if ($_GET['action_'.$arResult['GRID_ID']] == 'delete')
	{
		global $USER_FIELD_MANAGER;
		$_GET['ID'] = (int)$_GET['ID'];
		$_GET['OWNER_ID'] = (int)$_GET['OWNER_ID'];
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields('CALENDAR_EVENT', $_GET['ID'], LANGUAGE_ID);
		if (isset($arUserFields['UF_CRM_CAL_EVENT']))
		{
			if (count($arUserFields['UF_CRM_CAL_EVENT']['VALUE']) > 1)
			{
				if (($k = array_search($_GET['REL_ID'], $arUserFields['UF_CRM_CAL_EVENT']['VALUE'])) !== false)
				{
					unset($arUserFields['UF_CRM_CAL_EVENT']['VALUE'][$k]);
					$USER_FIELD_MANAGER->Update('CALENDAR_EVENT', $_GET['ID'], array('UF_CRM_CAL_EVENT' => $arUserFields['UF_CRM_CAL_EVENT']['VALUE']));
				}
			}
			else
				CCalendar::DeleteEvent($_GET['ID']);
		}

		unset($_GET['ID'], $_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_GET['AJAX_CALL']))
		LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_activity' : '');
}

$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);

if (isset($_REQUEST['clear_filter']) && $_REQUEST['clear_filter'] == 'Y')
{
	$urlParams = array();
	foreach($arResult['FILTER'] as $id => $arFilter)
	{
		if ($arFilter['type'] == 'user')
		{
			$urlParams[] = $arFilter['id'];
			$urlParams[] = $arFilter['id'].'_name';
		}
		else
			$urlParams[] = $arFilter['id'];
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	LocalRedirect($APPLICATION->GetCurPageParam('', $urlParams));
}

$arNav = $CGridOptions->GetNavParams($arNavParams);

$_arSort = $CGridOptions->GetSorting(array(
	'sort' => array('created_date' => 'desc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);

// converts data from filter
foreach ($arFilter as $k => $v)
{
	$arMatch = array();
	if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		$arFilter['>='.$arMatch[1]] = $v;
		unset($arFilter[$k]);
	}
	else if (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if (($arMatch[1] == 'CREATED_DATE')
			&& !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
			$v = CCrmDateTimeHelper::SetMaxDayTime($v);

		$arFilter['<='.$arMatch[1]] = $v;
		unset($arFilter[$k]);
	}
}

// remove column for deleted UF
$arSelect = $CGridOptions->GetVisibleColumns();

if (empty($arSelect))
{
	$arSelect = array();
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
			$arSelect[] = $arHeader['id'];
	}
}

$arResult['SELECTED_HEADERS'] = $arSelect;
$nPageTop = false;
if ($arResult['GADGET'] == 'Y')
{
	$arSelect = array('CREATED_DATE', 'TITLE', 'OWNER_ID');
	$nPageTop = $arParams['ACTIVITY_CALENDAR_COUNT'];
}

$arSelect[] = 'UF_CRM_CAL_EVENT';
if (!in_array('ID', $arSelect))
	$arSelect[] = 'ID';

$obRes = CCrmActivityCalendar::GetList($arResult['SORT'], $arFilter, $arSelect, $nPageTop);
if ($arResult['GADGET'] != 'Y')
{
	$obRes->NavStart($arNav['nPageSize'], false);
	$arResult['DB_LIST'] = $obRes;
}
$obRes->bShowAll = false;
$arResult['ROWS_COUNT'] = $obRes->NavRecordCount;
$arResult['CAL'] = array();
$arCalList = array();
$i = 0;
$arCalendarConf = CCalendar::GetSettings();
$arParams['PATH_TO_CAL_SHOW'] = $arCalendarConf['path_to_user_calendar'];

while($arCal = $obRes->GetNext())
{
	if (!isset($arCal['~UF_CRM_CAL_EVENT']) || !is_array($arCal['~UF_CRM_CAL_EVENT']))
		continue;

	$iAddTask = -1;
	foreach ($arCal['~UF_CRM_CAL_EVENT'] as $sCalRel)
	{
		if ($nPageTop !== false && $i >= $nPageTop)
			break 2;

		$arCal['REL_ID'] = $sCalRel;
		$arCalEntityData = CCrmActivityCalendar::GetEntityDataByCalRel($sCalRel);

		if (isset($arResult['CAL'][$arCal['ID'].'_'.$sCalRel]))
			continue ;
		if (!empty($arParams['INTERNAL_FILTER']['ENTITY_TYPE']) && $arParams['INTERNAL_FILTER']['ENTITY_TYPE'] != $arCalEntityData['TYPE'])
			continue;
		if (!empty($arParams['INTERNAL_FILTER']['ENTITY_ID']) && $arParams['INTERNAL_FILTER']['ENTITY_ID'] != $arCalEntityData['ID'])
			continue;
		$iAddTask++;

		$arCal['PATH_TO_CALENDAR_SHOW'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CAL_SHOW'],
			array(
				'user_id' => $arCal['OWNER_ID']
			)),
			array(
				'EVENT_ID' => $arCal['ID']
			)
		);

		$arCal['PATH_TO_CALENDAR_DELETE'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CAL_LIST'],
			array()),
			array(
				'action_'.$arResult['GRID_ID'] => 'delete', 'sessid' => bitrix_sessid(),
				'ID' => $arCal['ID'], 'OWNER_ID' => $arCal['OWNER_ID'],
				'REL_ID' => $sCalRel
			)
		);

		$arCal['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
			array(
				'user_id' => $arCal['~OWNER_ID']
			)
		);

		if (!is_array($arCal['~OWNER_ID']) && intval($arCal['~OWNER_ID']) > 0)
		{
			$rsUser = CUser::GetByID(intVal($arCal['~OWNER_ID']));
			$arUser = $rsUser->Fetch();
			$arCal['OWNER_ID'] = CUser::FormatName($arParams["NAME_TEMPLATE"], $arUser, true);
		}

		$arCal['ENTITY_TYPE'] = $arCalEntityData['TYPE'];
		$arCal['ENTITY_ID'] = $arCalEntityData['ID'];
		$arCalList[$arCalEntityData['TYPE']][$arCalEntityData['ID']] = array();

		$arResult['CAL'][$arCal['ID'].'_'.$sCalRel] = $arCal;
		$i++;
	}

	if ($iAddTask != 0)
		$arResult['ROWS_COUNT'] += $iAddTask;
}

if ($arResult['ACTIVITY_ENTITY_LINK'] == 'Y')
{
	if (isset($arCalList['LEAD']) && !empty($arCalList['LEAD']))
	{
		$dbRes = CCrmLead::GetListEx(Array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('@ID' => array_keys($arCalList['LEAD'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arCalList['LEAD'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'], array('lead_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arCalList['CONTACT']) && !empty($arCalList['CONTACT']))
	{
		$dbRes = CCrmContact::GetListEx(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('@ID' => array_keys($arCalList['CONTACT'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arCalList['CONTACT'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['LAST_NAME'].' '.$arRes['NAME'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arCalList['COMPANY']) && !empty($arCalList['COMPANY']))
	{
		$dbRes = CCrmCompany::GetListEx(Array('TITLE'=>'ASC'), array('@ID' => array_keys($arCalList['COMPANY'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arCalList['COMPANY'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arCalList['DEAL']) && !empty($arCalList['DEAL']))
	{
		$dbRes = CCrmDeal::GetListEx(Array('TITLE'=>'ASC'), array('@ID' => array_keys($arCalList['DEAL'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arCalList['DEAL'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $arRes['ID']))
			);
		}
	}

	foreach($arResult['CAL'] as $key => $ar)
	{
		$arResult['CAL'][$key]['ENTITY_TITLE'] = htmlspecialcharsbx($arCalList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_TITLE']);
		$arResult['CAL'][$key]['ENTITY_LINK'] = $arCalList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_LINK'];
	}
}
$this->IncludeComponentTemplate();

return $arResult['ROWS_COUNT'];

?>