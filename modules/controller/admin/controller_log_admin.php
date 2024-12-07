<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */
/** @var CAdminSidePanelHelper $adminSidePanelHelper */

if (!$USER->CanDoOperation('controller_log_view') || !CModule::IncludeModule('controller'))
{
	$APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/controller/prolog.php';

IncludeModuleLangFile(__FILE__);

$sTableID = 't_controll_log';
$oSort = new CAdminUiSorting($sTableID, 'id', 'desc');
$lAdmin = new CAdminUiList($sTableID, $oSort);
/** @var string $by */
/** @var string $order */
$arLogNames = CControllerLog::GetNameArray();
$arTaskNames = CControllerTask::GetTaskArray();

$filterFields = [
	[
		'id' => 'NAME',
		'name' => GetMessage('CTRL_LOG_ADMIN_COLUMN_NAME'),
		'type' => 'list',
		'items' => $arLogNames,
		'params' => ['multiple' => 'Y'],
		'filterable' => '=',
	],
	[
		'id' => 'STATUS',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_STATUS'),
		'type' => 'list',
		'items' => [
			'Y' => GetMessage('CTRL_LOG_ADMIN_COLUMN_STATUS_OK'),
			'N' => GetMessage('CTRL_LOG_ADMIN_COLUMN_STATUS_ERR'),
		],
		'filterable' => '=',
	],
	[
		'id' => 'DESCRIPTION',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_DESC'),
		'filterable' => '%',
	],
	[
		'id' => 'ID',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_ID'),
		'filterable' => '=',
	],
	[
		'id' => 'CONTROLLER_MEMBER_NAME',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_CLIENT'),
		'filterable' => '%',
	],
	[
		'id' => 'CONTROLLER_MEMBER_ID',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_IDCLIENT'),
		'filterable' => '=',
	],
	[
		'id' => 'TASK_NAME',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_TASK'),
		'filterable' => '%',
	],
	[
		'id' => 'TASK_ID',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_TASKID'),
		'filterable' => '=',
	],
	[
		'id' => 'TIMESTAMP_X',
		'name' => GetMessage('CTRL_LOG_ADMIN_FILTER_CREATED'),
		'type' => 'date',
	],
];

$arFilter = [];
$lAdmin->AddFilter($filterFields, $arFilter);

$filterOption = new Bitrix\Main\UI\Filter\Options($sTableID);
$filterData = $filterOption->getFilter($filterFields);
if (!empty($filterData['FIND']))
{
	$arFilter['=ID'] = $filterData['FIND'];
}

$arID = $lAdmin->GroupAction();
if ($arID && $USER->CanDoOperation('controller_log_delete'))
{
	if ($_REQUEST['action_target'] == 'selected')
	{
		$rsData = CControllerLog::GetList([], $arFilter, false, ['ID']);
		while ($arRes = $rsData->Fetch())
		{
			$arID[] = $arRes['ID'];
		}
	}

	foreach ($arID as $ID)
	{
		if ($ID == '')
		{
			continue;
		}
		$ID = intval($ID);

		if ($_REQUEST['action'] === 'delete')
		{
			@set_time_limit(0);
			$DB->StartTransaction();
			if (!CControllerLog::Delete($ID))
			{
				$DB->Rollback();
				$lAdmin->AddGroupError(GetMessage('CTRL_LOG_ADMIN_ERR_DELETE'), $ID);
			}
			else
			{
				$DB->Commit();
			}
		}
	}

	if ($lAdmin->hasGroupErrors())
	{
		$adminSidePanelHelper->sendJsonErrorResponse($lAdmin->getGroupErrors());
	}
	else
	{
		$adminSidePanelHelper->sendSuccessResponse();
	}
}

$arHeaders = [
	[
		'id' => 'TIMESTAMP_X',
		'content' => GetMessage('CTRL_LOG_ADMIN_COLUMN_CREATED'),
		'default' => true,
		'sort' => 'timestamp_x',
	],
	[
		'id' => 'NAME',
		'content' => GetMessage('CTRL_LOG_ADMIN_COLUMN_NAME'),
		'default' => true,
		'sort' => 'name',
	],
	[
		'id' => 'CONTROLLER_MEMBER_NAME',
		'content' => GetMessage('CTRL_LOG_ADMIN_FILTER_CLIENT'),
		'default' => true,
		'sort' => 'controller_member_name',
	],
	[
		'id' => 'STATUS',
		'content' => GetMessage('CTRL_LOG_ADMIN_FILTER_STATUS'),
		'default' => true,
		'sort' => 'status',
	],
	[
		'id' => 'TASK_NAME',
		'content' => GetMessage('CTRL_LOG_ADMIN_FILTER_TASK'),
		'default' => true,
		'sort' => 'task_name',
	],
	[
		'id' => 'USER',
		'content' => GetMessage('CTRL_LOG_ADMIN_COLUMN_USER'),
		'default' => true,
	],
	[
		'id' => 'DESCRIPTION',
		'content' => GetMessage('CTRL_LOG_ADMIN_FILTER_DESC'),
	],
	[
		'id' => 'ID',
		'content' => 'ID',
		'default' => true,
		'sort' => 'id',
	],
];

$lAdmin->AddHeaders($arHeaders);

$nav = $lAdmin->getPageNavigation('pages-controller-log-admin');

$arNavParams = false;
if ($lAdmin->isTotalCountRequest())
{
	$count = CControllerLog::GetList(
		[],
		$arFilter,
		['bOnlyCount' => true],
		['ID']
	);
	$lAdmin->sendTotalCountResponse($count);
}
elseif ($_REQUEST['mode'] !== 'excel')
{
	$arNavParams = [
		'nTopCount' => $nav->getLimit() + 1,
		'nOffset' => $nav->getOffset(),
	];
}

$arSelect = $lAdmin->GetVisibleHeaderColumns();
$arSelect[] = 'ID';
if (in_array('USER', $arSelect, true))
{
	$arSelect[] = 'USER_ID';
	$arSelect[] = 'USER_LOGIN';
	$arSelect[] = 'USER_NAME';
	$arSelect[] = 'USER_LAST_NAME';
}
if (in_array('CONTROLLER_MEMBER_NAME', $arSelect, true))
{
	$arSelect[] = 'CONTROLLER_MEMBER_ID';
}

$rsData = CControllerLog::GetList(
	[
		$by => $order,
	],
	$arFilter,
	$arNavParams,
	$arSelect
);
$rsData = new CAdminResult($rsData, $sTableID);

$n = 0;
$pageSize = $lAdmin->getNavSize();
while ($arRes = $rsData->Fetch())
{
	$n++;
	if ($n > $pageSize && !($_REQUEST['mode'] == 'excel'))
	{
		break;
	}

	$row =& $lAdmin->AddRow($arRes['ID'], $arRes);

	$htmlLink = 'controller_member_edit.php?lang=' . LANGUAGE_ID . '&ID=' . urlencode($arRes['CONTROLLER_MEMBER_ID']);
	$row->AddViewField('CONTROLLER_MEMBER_NAME', '[<a href="' . htmlspecialcharsbx($htmlLink) . '">' . htmlspecialcharsEx($arRes['CONTROLLER_MEMBER_ID']) . '</a>] ' . htmlspecialcharsEx($arRes['CONTROLLER_MEMBER_NAME']) . '</a>');

	if ($arRes['TASK_ID'] > 0)
	{
		$row->AddViewField('TASK_NAME', htmlspecialcharsEx($arTaskNames[$arRes['TASK_NAME']] . ' [' . $arRes['TASK_ID'] . ']'));
	}

	$row->AddViewField('NAME', (isset($arLogNames[$arRes['NAME']]) ? htmlspecialcharsEx($arLogNames[$arRes['NAME']]) : $arRes['NAME']));

	if ($arRes['USER_ID'] > 0)
	{
		$htmlName = '(' . $arRes['USER_LOGIN'] . ') ' . $arRes['USER_NAME'] . ' ' . $arRes['USER_LAST_NAME'];
		adminListAddUserLink($row, 'USER', $arRes['USER_ID'], $htmlName);
	}

	$row->AddViewField('STATUS', ($arRes['STATUS'] == 'Y' ? GetMessage('CTRL_LOG_ADMIN_COLUMN_STATUS_OK') : GetMessage('CTRL_LOG_ADMIN_COLUMN_STATUS_ERR')));

	$arActions = [];

	$arActions[] = [
		'ICON' => 'list',
		'TEXT' => GetMessage('CTRL_LOG_ADMIN_MENU_DETAIL'),
		'ACTION' => "jsUtils.OpenWindow('" . CUtil::JSEscape('controller_log_detail.php?lang=' . LANGUAGE_ID . '&ID=' . urlencode($arRes['ID'])) . "', '700', '550');",
		'DEFAULT' => 'Y',
	];

	if ($USER->CanDoOperation('controller_log_delete'))
	{
		$arActions[] = [
			'ICON' => 'delete',
			'TEXT' => GetMessage('CTRL_LOG_ADMIN_MENU_DEL'),
			'ACTION' => "if(confirm('" . GetMessage('CTRL_LOG_ADMIN_MENU_DEL_CONFIRM') . "')) " . $lAdmin->ActionDoGroup($arRes['ID'], 'delete'),
		];
	}

	$row->AddActions($arActions);
}

$nav->setRecordCount($nav->getOffset() + $n);
$lAdmin->setNavigation($nav, GetMessage('CTRL_LOG_ADMIN_PAGETITLE'), false);

$lAdmin->AddFooter([
	[
		'title' => GetMessage('MAIN_ADMIN_LIST_SELECTED'),
		'value' => $rsData->SelectedRowsCount(),
	],
	[
		'counter' => true,
		'title' => GetMessage('MAIN_ADMIN_LIST_CHECKED'),
		'value' => '0',
	],
]);

if ($USER->CanDoOperation('controller_log_delete'))
{
	$lAdmin->AddGroupActionTable(
		[
			'delete' => GetMessage('MAIN_ADMIN_LIST_DELETE'),
		]
	);
}

$lAdmin->AddAdminContextMenu();

$lAdmin->CheckListMode();

$APPLICATION->SetTitle(GetMessage('CTRL_LOG_ADMIN_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin_after.php';

$lAdmin->DisplayFilter($filterFields);
$lAdmin->DisplayList(['SHOW_COUNT_HTML' => true]);

require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';
