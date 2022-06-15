<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 */

use Bitrix\Main\Localization\Loc;

$arResult['CAN_WRITE'] = $USER->CanDoOperation('biconnector_dashboard_manage');
$arResult['CAN_READ'] = $arResult['CAN_WRITE'] || $USER->CanDoOperation('biconnector_dashboard_view');

if (!$arResult['CAN_WRITE'] && !$arResult['CAN_READ'])
{
	ShowError(Loc::getMessage('ACCESS_DENIED'));
	return;
}

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBDL_ERROR_INCLUDE_MODULE'));
	return;
}

if (
	$arResult['CAN_WRITE']
	&& $_SERVER['REQUEST_METHOD'] === 'POST'
	&& check_bitrix_sessid()
)
{
	if ($_POST['action'] === 'deleteRow')
	{
		$board_id = intval($_POST['id']);
		if ($board_id > 0)
		{
			\Bitrix\BIConnector\DashboardUserTable::deleteByFilter(['=DASHBOARD_ID' => $board_id]);

			$deleteResult = \Bitrix\BIConnector\DashboardTable::delete($board_id);
			$deleteResult->isSuccess();
		}
	}
}

$APPLICATION->SetTitle(Loc::getMessage('CC_BBDL_TITLE'));

$arResult['GRID_ID'] = 'biconnector_dashboard_list';
$arResult['SORT'] = ['NAME' => 'ASC'];
$arResult['ROWS'] = [];

$filter = [];
if (!$arResult['CAN_WRITE'])
{
	$filter['=PERMISSION.USER_ID'] = $USER->getId();
}

$keyList = \Bitrix\BIConnector\DashboardTable::getList([
	'select' => [
		'ID',
		'DATE_CREATE',
		'CREATED_BY',
		'CREATED_USER.NAME',
		'CREATED_USER.LAST_NAME',
		'CREATED_USER.SECOND_NAME',
		'CREATED_USER.EMAIL',
		'CREATED_USER.LOGIN',
		'CREATED_USER.PERSONAL_PHOTO',
		'NAME',
		'URL',
	],
	'filter' => $filter,
	'order' => $arResult['SORT'],
]);
while ($data = $keyList->fetch())
{

	$userEmptyAvatar = ' dashboard-grid-avatar-empty';
	$userAvatar = '';

	$userName = \CUser::FormatName(
		\CSite::GetNameFormat(false),
		[
			'ID' => $data['CREATED_BY'],
			'NAME' => $data['BICONNECTOR_DASHBOARD_CREATED_USER_NAME'],
			'LAST_NAME' => $data['BICONNECTOR_DASHBOARD_CREATED_USER_LAST_NAME'],
			'SECOND_NAME' => $data['BICONNECTOR_DASHBOARD_CREATED_USER_SECOND_NAME'],
			'EMAIL' => $data['BICONNECTOR_DASHBOARD_CREATED_USER_EMAIL'],
			'LOGIN' => $data['BICONNECTOR_DASHBOARD_CREATED_USER_LOGIN'],
		],
		true
	);

	$fileInfo = \CFile::ResizeImageGet(
		$data['BICONNECTOR_DASHBOARD_CREATED_USER_PERSONAL_PHOTO'],
		['width' => 60, 'height' => 60],
		BX_RESIZE_IMAGE_EXACT
	);
	if (is_array($fileInfo) && isset($fileInfo['src']))
	{
		$userEmptyAvatar = '';
		$photoUrl = $fileInfo['src'];
		$userAvatar = " style='background-image: url(\"{$photoUrl}\")'";
	}

	$userNameElement = '<span class="dashboard-grid-avatar ui-icon ui-icon-common-user' . $userEmptyAvatar . '">'
		. '<i' . $userAvatar . '></i>'
		. '</span>'
		. '<span class="dashboard-grid-username-inner">' . $userName . '</span>';

	$data['CREATED_BY'] = '<div class="dashboard-grid-username-wrapper">'
		.'<a class="dashboard-grid-username" href="/company/personal/user/' . $data['CREATED_BY'] . '/">' . $userNameElement . '</a>'
		.'</div>';

	$url = str_replace('#ID#', urlencode($data['ID']), $arParams['DASHBOARD_VIEW_URL']);

	$displayUrl = mb_strlen($data['URL']) > 100 ? mb_substr($data['URL'], 0, 97) . '...' : $data['URL'];
	$data['URL'] = '<a href="' . htmlspecialcharsBx('javascript:BX.SidePanel.Instance.open(\'' . CUtil::JSEscape($url) . '\')') . '">' . htmlspecialcharsEx($displayUrl) . '</a>';
	$data['DATE_CREATE'] = preg_replace('/([0-9]{2}:[0-9]{2}):[0-9]{2}/', '\\1', $data['DATE_CREATE']);
	$data['NAME'] = htmlspecialcharsEx($data['NAME']);

	$actions = [];

	$actions[] = [
		'ID' => 'edit',
		'TEXT' => Loc::getMessage('CC_BBDL_ACTION_MENU_VIEW'),
		'ONCLICK' => 'BX.SidePanel.Instance.open(\'' . CUtil::JSEscape($url) . '\')',
		'DEFAULT' => !$arResult['CAN_WRITE'],
	];

	if ($arResult['CAN_WRITE'])
	{
		$url = str_replace('#ID#', urlencode($data['ID']), $arParams['DASHBOARD_EDIT_URL']);
		$actions[] = [
			'ID' => 'edit',
			'TEXT' => Loc::getMessage('CC_BBDL_ACTION_MENU_EDIT'),
			'ONCLICK' => 'BX.SidePanel.Instance.open(\'' . CUtil::JSEscape($url) . '\')',
			'DEFAULT' => true,
		];

		$actions[] = [
			'ID' => 'delete',
			'TEXT' => Loc::getMessage('CC_BBDL_ACTION_MENU_DELETE'),
			'ONCLICK' => 'BX.Main.gridManager.getInstanceById(\'' . $arResult['GRID_ID'] . '\').confirmDialog({CONFIRM: true, CONFIRM_MESSAGE: \'' . Loc::getMessage('CC_BBDL_ACTION_MENU_DELETE_CONF') . '\'}, function(){BX.Main.gridManager.getInstanceById(\'' . $arResult['GRID_ID'] . '\').removeRow(\'' . $data['ID'] . '\')})',
		];
	}

	$arResult['ROWS'][] = [
		'id' => $data['ID'],
		'data' => $data,
		'actions' => $actions,
	];
}

$this->IncludeComponentTemplate();
