<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
/** @var CMain $APPLICATION */
/** @var CDatabase $DB */
/** @var CUser $USER */
use Bitrix\Main\Localization\Loc;
use \Bitrix\Controller\AuthLogTable;

if (!$USER->CanDoOperation('controller_auth_log_view') || !\Bitrix\Main\Loader::includeModule('controller'))
{
	$APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}
require_once $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/controller/prolog.php';

Loc::loadMessages(__FILE__);

$tableID = 't_controller_auth_log';
$sorting = new CAdminUiSorting($tableID, 'ID', 'DESC');
/** @var string $by */
/** @var string $order */
$adminList = new CAdminUiList($tableID, $sorting);

$filterFields = [
	[
		'id' => 'USER_ID',
		'name' => GetMessage('CONTROLLER_AUTH_LOG_USER_ID'),
		'filterable' => '=',
	],
	[
		'id' => 'FROM_CONTROLLER_MEMBER_ID',
		'name' => GetMessage('CONTROLLER_AUTH_LOG_FROM_CONTROLLER_MEMBER'),
		'filterable' => '=',
	],
	[
		'id' => 'TO_CONTROLLER_MEMBER_ID',
		'name' => GetMessage('CONTROLLER_AUTH_LOG_TO_CONTROLLER_MEMBER'),
		'filterable' => '=',
		'default' => true,
	],
	[
		'id' => 'TIMESTAMP_X',
		'name' => GetMessage('CONTROLLER_AUTH_LOG_TIMESTAMP_X'),
		'type' => 'date',
	],
];

$arFilter = [];
$adminList->AddFilter($filterFields, $arFilter);
if (preg_match('/\D+/', $arFilter['=FROM_CONTROLLER_MEMBER_ID']))
{
	$arFilter['=FROM_CONTROLLER_MEMBER.NAME'] = $arFilter['=FROM_CONTROLLER_MEMBER_ID'];
	unset($arFilter['=FROM_CONTROLLER_MEMBER_ID']);
}
if (preg_match('/\D+/', $arFilter['=TO_CONTROLLER_MEMBER_ID']))
{
	$arFilter['=TO_CONTROLLER_MEMBER.NAME'] = $arFilter['=TO_CONTROLLER_MEMBER_ID'];
	unset($arFilter['=TO_CONTROLLER_MEMBER_ID']);
}

$nav = $adminList->getPageNavigation('nav-controller-auth-log');

$authLogList = AuthLogTable::getList([
	'select' => [
		'ID',
		'TIMESTAMP_X',
		'FROM_CONTROLLER_MEMBER_ID',
		'FROM_CONTROLLER_MEMBER.NAME',
		'TO_CONTROLLER_MEMBER_ID',
		'TO_CONTROLLER_MEMBER.NAME',
		'TYPE',
		'USER_ID',
		'USER_NAME',
	],
	'filter' => $arFilter,
	'order' => [mb_strtoupper($by) => $order],
	'count_total' => true,
	'offset' => $nav->getOffset(),
	'limit' => $nav->getLimit(),
]);

$nav->setRecordCount($authLogList->getCount());

$adminList->setNavigation($nav, Loc::getMessage('CONTROLLER_AUTH_LOG_PAGES'));

$adminList->AddHeaders([
	[
		'id' => 'ID',
		'content' => Loc::getMessage('CONTROLLER_AUTH_LOG_ID'),
		'sort' => 'ID',
		'default' => true,
	],
	[
		'id' => 'TIMESTAMP_X',
		'content' => Loc::getMessage('CONTROLLER_AUTH_LOG_TIMESTAMP_X'),
		'sort' => 'TIMESTAMP_X',
		'default' => true,
	],
	[
		'id' => 'FROM_CONTROLLER_MEMBER',
		'content' => Loc::getMessage('CONTROLLER_AUTH_LOG_FROM_CONTROLLER_MEMBER'),
		'default' => true,
	],
	[
		'id' => 'TO_CONTROLLER_MEMBER',
		'content' => Loc::getMessage('CONTROLLER_AUTH_LOG_TO_CONTROLLER_MEMBER'),
		'default' => true,
	],
	[
		'id' => 'TYPE',
		'content' => Loc::getMessage('CONTROLLER_AUTH_LOG_TYPE'),
		'default' => true,
	],
	[
		'id' => 'USER',
		'content' => Loc::getMessage('CONTROLLER_AUTH_LOG_USER'),
		'default' => true,
	],
]);

while ($authLog = $authLogList->fetch())
{
	$row = &$adminList->AddRow(intval($authLog['ID']), $authLog);

	$row->AddViewField('ID', htmlspecialcharsEx($authLog['ID']));
	$row->AddViewField('TIMESTAMP_X', htmlspecialcharsEx($authLog['TIMESTAMP_X']));

	if ($authLog['FROM_CONTROLLER_MEMBER_ID'] > 0)
	{
		$htmlLink = 'controller_member_edit.php?lang=' . LANGUAGE_ID . '&ID=' . urlencode($authLog['FROM_CONTROLLER_MEMBER_ID']);
		$htmlName = $authLog['CONTROLLER_AUTH_LOG_FROM_CONTROLLER_MEMBER_NAME'] . ' [' . $authLog['FROM_CONTROLLER_MEMBER_ID'] . ']';
		$row->AddViewField('FROM_CONTROLLER_MEMBER', '<a href="' . htmlspecialcharsbx($htmlLink) . '">' . htmlspecialcharsEx($htmlName) . '</a>');
	}

	if ($authLog['TO_CONTROLLER_MEMBER_ID'] > 0)
	{
		$htmlLink = 'controller_member_edit.php?lang=' . LANGUAGE_ID . '&ID=' . urlencode($authLog['TO_CONTROLLER_MEMBER_ID']);
		$htmlName = $authLog['CONTROLLER_AUTH_LOG_TO_CONTROLLER_MEMBER_NAME'] . ' [' . $authLog['TO_CONTROLLER_MEMBER_ID'] . ']';
		$row->AddViewField('TO_CONTROLLER_MEMBER', '<a href="' . htmlspecialcharsbx($htmlLink) . '">' . htmlspecialcharsEx($htmlName) . '</a>');
	}

	if ($authLog['USER_ID'] > 0)
	{
		$htmlLink = 'user_edit.php?lang=' . LANGUAGE_ID . '&ID=' . urlencode($authLog['USER_ID']);
		$htmlName = $authLog['USER_NAME'];
		$row->AddViewField('USER', '<a href="' . htmlspecialcharsbx($htmlLink) . '">' . htmlspecialcharsEx($htmlName) . '</a>');
	}
	elseif ($authLog['USER_NAME'] <> '')
	{
		$row->AddViewField('USER', htmlspecialcharsEx($authLog['USER_NAME']));
	}
}

$adminList->AddAdminContextMenu([]);

$adminList->CheckListMode();

$APPLICATION->SetTitle(Loc::getMessage('CONTROLLER_AUTH_LOG_TITLE'));

require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/prolog_admin_after.php';

$adminList->DisplayFilter($filterFields);
$adminList->DisplayList();

require $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/include/epilog_admin.php';
