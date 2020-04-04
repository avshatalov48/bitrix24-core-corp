<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('tasks'))
{
	ShowError(GetMessage('TASKS_MODULE_NOT_INSTALLED'));
	return;
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

if (empty($arParams['ACTIVITY_TASK_COUNT']))
	$arParams['ACTIVITY_TASK_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['ACTIVITY_TASK_COUNT']
);
$arNavigation = CDBResult::GetNavParams($arNavParams);

global $APPLICATION;

$arResult['GRID_ID'] = 'CRM_ACTIVITY_TASK_LIST'.($bInternal ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['STATUS_LIST'] = array(1 => GetMessage('TASKS_STATUS_1'), 2 => GetMessage('TASKS_STATUS_2'), 3 => GetMessage('TASKS_STATUS_3'), 4 => GetMessage('TASKS_STATUS_4'), 5 => GetMessage('TASKS_STATUS_5'), 6 => GetMessage('TASKS_STATUS_6'), 7 => GetMessage('TASKS_STATUS_7'));
$arResult['PRIORITY_LIST'] = array(0 => GetMessage('TASKS_PRIORITY_0'), 1 => GetMessage('TASKS_PRIORITY_1'), 2 => GetMessage('TASKS_PRIORITY_2'));
$arResult['FILTER'] = array();
$arResult['FILTER_PRESETS'] = array();

if (!$bInternal)
{
	$arEntityType = Array(
		'' => '',
		'LEAD' => GetMessage('CRM_ENTITY_TYPE_LEAD'),
		'CONTACT' => GetMessage('CRM_ENTITY_TYPE_CONTACT'),
		'COMPANY' => GetMessage('CRM_ENTITY_TYPE_COMPANY'),
		'DEAL' => GetMessage('CRM_ENTITY_TYPE_DEAL'),
	);

	ob_start();
	$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => Array('LEAD', 'CONTACT', 'COMPANY', 'DEAL'),
			'INPUT_NAME' => 'UF_CRM_TASK',
			'INPUT_VALUE' => isset($_REQUEST['UF_CRM_TASK']) ? $_REQUEST['UF_CRM_TASK'] : '',
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
		array('id' => 'UF_CRM_TASK', 'name' => GetMessage('CRM_COLUMN_UF_CRM_TASK'), 'type' => 'custom', 'value' => $sVal),
		array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'type' => 'list', 'items' => $arEntityType),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'default' => 'Y'),
		array('id' => 'REAL_STATUS', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_REAL_STATUS'), 'type' => 'list', 'items' => array('' => '') + $arResult['STATUS_LIST']),
		array('id' => 'PRIORITY', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_PRIORITY'), 'type' => 'list', 'items' => array('' => '') + $arResult['PRIORITY_LIST']),
		array('id' => 'CREATED_DATE', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'type' => 'date'),
		array('id' => 'CHANGED_DATE', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'type' => 'date'),
		array('id' => 'DATE_START', 'name' => GetMessage('CRM_COLUMN_DATE_START'), 'type' => 'date'),
		array('id' => 'CLOSED_DATE', 'name' => GetMessage('CRM_COLUMN_CLOSED_DATE'), 'type' => 'date'),
		array('id' => 'RESPONSIBLE_ID',  'name' => GetMessage('CRM_COLUMN_RESPONSIBLE_BY'), 'default' => false, 'enable_settings' => false, 'type' => 'user'),
		array('id' => 'CHANGED_ID',  'name' => GetMessage('CRM_COLUMN_CHANGED_BY'), 'default' => false, 'enable_settings' => false, 'type' => 'user')
	);

	$arResult['FILTER_PRESETS'] = array(
		'filter_new' => array('name' => GetMessage('CRM_PRESET_NEW'), 'fields' => array('REAL_STATUS' => '1')),
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'fields' => array( 'RESPONSIBLE_ID'=>__format_user4search(), 'RESPONSIBLE_ID[]'=>$GLOBALS['USER']->GetID())),
		'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CHANGE_TODAY'), 'fields' => array('CHANGED_DATE_datesel' => 'today')),
		'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CHANGE_YESTERDAY'), 'fields' => array('CHANGED_DATE_datesel' => 'yesterday')),
		'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CHANGE_MY'), 'fields' =>array( 'CHANGED_ID'=>__format_user4search(), 'CHANGED_ID[]'=>$GLOBALS['USER']->GetID()))
	);
}

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'editable' => false, 'type' => 'int'),
	array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'default' => true, 'editable' => true)
);
if ($arResult['ACTIVITY_ENTITY_LINK'] == 'Y')
{
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TYPE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TYPE'), 'sort' => false, 'default' => true, 'editable' => false);
	$arResult['HEADERS'][] = array('id' => 'ENTITY_TITLE', 'name' => GetMessage('CRM_COLUMN_ENTITY_TITLE'), 'sort' => false, 'default' => true, 'editable' => false);
}
$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'REAL_STATUS', 'name' => GetMessage('CRM_COLUMN_REAL_STATUS'), 'sort' => 'real_status', 'default' => true, 'editable' => array(), 'type' => 'list'),
	array('id' => 'PRIORITY', 'name' => GetMessage('CRM_COLUMN_PRIORITY'), 'sort' => 'priority', 'default' => true, 'editable' => array(), 'type' => 'list'),
	array('id' => 'CREATED_DATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'created_date', 'default' => true),
	array('id' => 'CHANGED_DATE', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'changed_date', 'default' => false),
	array('id' => 'DATE_START', 'name' => GetMessage('CRM_COLUMN_DATE_START'), 'sort' => 'date_start', 'default' => false, 'editable' => true, 'type' => 'date'),
	array('id' => 'CLOSED_DATE', 'name' => GetMessage('CRM_COLUMN_CLOSED_DATE'), 'sort' => 'closed_date', 'default' => true, 'editable' => true, 'type' => 'date'),
	array('id' => 'RESPONSIBLE_ID', 'name' => GetMessage('CRM_COLUMN_RESPONSIBLE_BY'), 'sort' => 'responsible_id', 'default' => true, 'editable' => false)
));



if ($_SERVER['REQUEST_METHOD'] == 'GET'
	&& check_bitrix_sessid()
	&& isset($_GET['action_'.$arResult['GRID_ID']]))
{
	if ($_GET['action_'.$arResult['GRID_ID']] == 'delete')
	{
		global $USER_FIELD_MANAGER;
		$_GET['ID'] = (int)$_GET['ID'];
		$_GET['RESPONSIBLE_ID'] = (int)$_GET['RESPONSIBLE_ID'];
		$arUserFields = $USER_FIELD_MANAGER->GetUserFields('TASKS_TASK', $_GET['ID'], LANGUAGE_ID);
		if (isset($arUserFields['UF_CRM_TASK']))
		{
			if (count($arUserFields['UF_CRM_TASK']['VALUE']) > 1)
			{
				if (($k = array_search($_GET['REL_ID'], $arUserFields['UF_CRM_TASK']['VALUE'])) !== false)
				{
					unset($arUserFields['UF_CRM_TASK']['VALUE'][$k]);
					$USER_FIELD_MANAGER->Update('TASKS_TASK', $_GET['ID'], array('UF_CRM_TASK' => $arUserFields['UF_CRM_TASK']['VALUE']));
				}
			}
			else
			{
				$arResult['PATH_TO_TASK_DELETE'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_entry', ''),
					array(
						'task_id' => $_GET['ID'],
						'user_id' => $_GET['RESPONSIBLE_ID']
					)),
					array(
						'sessid' => bitrix_sessid(),
						'ACTION' => 'delete',
						'back_url' => urlencode(CHTTP::urlAddParams(
							CHTTP::urlDeleteParams(
								$APPLICATION->GetCurUri(),
								array('ID', 'REL_ID', 'RESPONSIBLE_ID', 'sessid', 'action_CRM_ACTIVITY_TASK_LIST',  'action_CRM_ACTIVITY_TASK_LIST_')
							),
							array(
								$arParams['FORM_ID'].'_active_tab' => $arResult['TAB_ID']
							)
						))
					)
				);

				if (!isset($_GET['AJAX_CALL']))
					LocalRedirect($arResult['PATH_TO_TASK_DELETE']);
			}
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
		if (($arMatch[1] == 'CREATED_DATE' || $arMatch[1] == 'CHANGED_DATE' || $arMatch[1] == 'DATE_START' || $arMatch[1] == 'CLOSED_DATE')
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
	$arSelect = array('CREATED_DATE', 'TITLE', 'REAL_STATUS', 'RESPONSIBLE_ID');
	$nPageTop = $arParams['ACTIVITY_TASK_COUNT'];
}

$arSelect[] = 'UF_CRM_TASK';
if (!in_array('ID', $arSelect))
	$arSelect[] = 'ID';

$obRes = CCrmActivityTask::GetList($arResult['SORT'], $arFilter, $arSelect, $nPageTop);
if ($arResult['GADGET'] != 'Y')
{
	$obRes->NavStart($arNav['nPageSize'], false);
	$arResult['DB_LIST'] = $obRes;
}
$obRes->bShowAll = false;
$arResult['ROWS_COUNT'] = $obRes->NavRecordCount;
$arResult['TASK'] = array();
$arTaskList = array();
$i = 0;
while($arTask = $obRes->GetNext())
{
	$iAddTask = -1;
	foreach ($arTask['~UF_CRM_TASK'] as $sTaskRel)
	{
		if ($nPageTop !== false && $i >= $nPageTop)
			break 2;

		$arTask['REL_ID'] = $sTaskRel;
		$arTaskEntityData = CCrmActivityTask::GetEntityDataByTaskRel($sTaskRel);

		if (isset($arResult['TASK'][$arTask['ID'].'_'.$sTaskRel]))
			continue ;
		if (!empty($arParams['INTERNAL_FILTER']['ENTITY_TYPE']) && $arParams['INTERNAL_FILTER']['ENTITY_TYPE'] != $arTaskEntityData['TYPE'])
			continue;
		if (!empty($arParams['INTERNAL_FILTER']['ENTITY_ID']) && $arParams['INTERNAL_FILTER']['ENTITY_ID'] != $arTaskEntityData['ID'])
			continue;

		$iAddTask++;

		$arTask['PATH_TO_TASK_SHOW'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_entry', ''),
			array(
				'task_id' => $arTask['ID'],
				'user_id' => $arTask['~RESPONSIBLE_ID']
			)
		);
		$arTask['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
			array(
				'task_id' => $arTask['ID'],
				'user_id' => $arTask['~RESPONSIBLE_ID']
			)),
			array(
				'back_url' => urlencode(CHTTP::urlAddParams($APPLICATION->GetCurUri(),
					array(
						$arResult['FORM_ID'].'_active_tab' => $arResult['TAB_ID']
					)
				))
			)
		);

		$arTask['PATH_TO_TASK_DELETE'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_TASK_LIST'],
			array()),
			array(
				'action_'.$arResult['GRID_ID'] => 'delete', 'sessid' => bitrix_sessid(),
				'ID' => $arTask['ID'], 'RESPONSIBLE_ID' => $arTask['~RESPONSIBLE_ID'],
				'REL_ID' => $sTaskRel
			)
		);
/*		$arTask['PATH_TO_TASK_DELETE'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_entry', ''),
			array(
				'task_id' => $arTask['ID'],
				'user_id' => $arTask['RESPONSIBLE_ID']
			)),
			array(
				'sessid' => bitrix_sessid(),
				'ACTION' => 'delete',
				'back_url' => urlencode(CHTTP::urlAddParams($APPLICATION->GetCurUri(),
					array(
						$arParams['FORM_ID'].'_active_tab' => $arResult['TAB_ID']
					)
				))
			)
		);*/

		$arTask['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
			array(
				'user_id' => $arTask['~RESPONSIBLE_ID']
			)
		);

		$arTask['RESPONSIBLE_FORMATTED_NAME'] = CUser::FormatName(
			$arParams['NAME_TEMPLATE'],
			array(
				'LOGIN'			=> $arTask['RESPONSIBLE_LOGIN'],
				'NAME'			=> $arTask['RESPONSIBLE_NAME'],
				'LAST_NAME'		=> $arTask['RESPONSIBLE_LAST_NAME'],
				'SECOND_NAME'	=> $arTask['RESPONSIBLE_SECOND_NAME']
			),
			true, false
		);

		$arTask['ENTITY_TYPE'] = $arTaskEntityData['TYPE'];
		$arTask['ENTITY_ID'] = $arTaskEntityData['ID'];
		$arTaskList[$arTaskEntityData['TYPE']][$arTaskEntityData['ID']] = array();

		$arResult['TASK'][$arTask['ID'].'_'.$sTaskRel] = $arTask;

		$i++;
	}

	if ($iAddTask != 0)
		$arResult['ROWS_COUNT'] += $iAddTask;
}

if ($arResult['ACTIVITY_ENTITY_LINK'] == 'Y')
{
	if (isset($arTaskList['LEAD']) && !empty($arTaskList['LEAD']))
	{
		$dbRes = CCrmLead::GetListEx(Array('TITLE'=>'ASC', 'LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('@ID' => array_keys($arTaskList['LEAD'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arTaskList['LEAD'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'], array('lead_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arTaskList['CONTACT']) && !empty($arTaskList['CONTACT']))
	{
		$dbRes = CCrmContact::GetListEx(Array('LAST_NAME'=>'ASC', 'NAME' => 'ASC'), array('@ID' => array_keys($arTaskList['CONTACT'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arTaskList['CONTACT'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => CCrmContact::PrepareFormattedName($arRes),
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arTaskList['COMPANY']) && !empty($arTaskList['COMPANY']))
	{
		$dbRes = CCrmCompany::GetListEx(Array('TITLE'=>'ASC'), array('@ID' => array_keys($arTaskList['COMPANY'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arTaskList['COMPANY'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $arRes['ID']))
			);
		}
	}
	if (isset($arTaskList['DEAL']) && !empty($arTaskList['DEAL']))
	{
		$dbRes = CCrmDeal::GetListEx(Array('TITLE'=>'ASC'), array('@ID' => array_keys($arTaskList['DEAL'])));
		while ($arRes = $dbRes->Fetch())
		{
			$arTaskList['DEAL'][$arRes['ID']] = Array(
				'ENTITY_TITLE' => $arRes['TITLE'],
				'ENTITY_LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $arRes['ID']))
			);
		}
	}

	foreach($arResult['TASK'] as $key => $ar)
	{
		$arResult['TASK'][$key]['ENTITY_TITLE'] = htmlspecialcharsbx($arTaskList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_TITLE']);
		$arResult['TASK'][$key]['ENTITY_LINK'] = $arTaskList[$ar['ENTITY_TYPE']][$ar['ENTITY_ID']]['ENTITY_LINK'];
	}
}

$this->IncludeComponentTemplate();

return $arResult['ROWS_COUNT'];

?>