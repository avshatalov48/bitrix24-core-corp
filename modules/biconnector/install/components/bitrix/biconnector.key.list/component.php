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

$arResult['CAN_WRITE'] = $USER->CanDoOperation('biconnector_key_manage');
$arResult['CAN_READ'] = $arResult['CAN_WRITE'] || $USER->CanDoOperation('biconnector_key_view');

if (!$arResult['CAN_WRITE'] && !$arResult['CAN_READ'])
{
	ShowError(Loc::getMessage('ACCESS_DENIED'));
	return;
}

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBKL_ERROR_INCLUDE_MODULE'));
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
		$key_id = intval($_POST['id']);
		if ($key_id > 0)
		{
			\Bitrix\BIConnector\KeyUserTable::deleteByFilter(['=KEY_ID' => $key_id]);
			\Bitrix\BIConnector\LogTable::deleteByFilter(['=KEY_ID' => $key_id]);

			$deleteResult = \Bitrix\BIConnector\KeyTable::delete($key_id);
			$deleteResult->isSuccess();
		}
	}
}

$APPLICATION->SetTitle(Loc::getMessage('CC_BBKL_TITLE'));

$arResult['CONNECTIONS'] = \Bitrix\BIConnector\Manager::getInstance()->getConnections();

$arResult['GRID_ID'] = 'biconnector_key_list';
$arResult['SORT'] = ['ID' => 'DESC'];
$arResult['ROWS'] = [];

$filter = [];
if (!$arResult['CAN_WRITE'] && !$USER->CanDoOperation('biconnector_key_view'))
{
	$filter['=PERMISSION.USER_ID'] = $USER->getId();
}

$keyList = \Bitrix\BIConnector\KeyTable::getList([
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
		'CONNECTION',
		'ACCESS_KEY',
		'ACTIVE',
		'APP_ID',
		'APPLICATION.APP_NAME',
	],
	'filter' => $filter,
	'order' => $arResult['SORT'],
]);
while ($data = $keyList->fetch())
{
	$accessKey = $data['ACCESS_KEY'] . LANGUAGE_ID;
	$data['ACCESS_KEY'] = '<input type="password" class="biconnector-key-grid-access-key" readonly value="' . htmlspecialcharsBx($accessKey) . '" size="' . strlen($accessKey) . '">'
		. '<button class="biconnector-key-grid-action-link" onclick="return showText(this, \'' . CUtil::JSEscape(Loc::getMessage('CT_BBKL_KEY_SHOW')) . '\', \'' . CUtil::JSEscape(Loc::getMessage('CT_BBKL_KEY_HIDE')) . '\')">' . Loc::getMessage('CT_BBKL_KEY_SHOW') . '</button>'
		. '<button class="biconnector-key-grid-action-link" onclick="return copyText(this, \'' . CUtil::JSEscape(Loc::getMessage('CT_BBKL_KEY_COPIED')) . '\')">' . Loc::getMessage('CT_BBKL_KEY_COPY') . '</button>'
	;
	if (count($arResult['CONNECTIONS']) < 2)
	{
		unset($data['CONNECTION']);
	}

	$data['ACTIVE'] = $data['ACTIVE'] == 'N' ? Loc::getMessage('MAIN_NO') : Loc::getMessage('MAIN_YES');

	$userEmptyAvatar = ' biconnector-key-grid-avatar-empty';
	$userAvatar = '';

	$userName = \CUser::FormatName(
		\CSite::GetNameFormat(false),
		[
			'ID' => $data['CREATED_BY'],
			'NAME' => $data['BICONNECTOR_KEY_CREATED_USER_NAME'],
			'LAST_NAME' => $data['BICONNECTOR_KEY_CREATED_USER_LAST_NAME'],
			'SECOND_NAME' => $data['BICONNECTOR_KEY_CREATED_USER_SECOND_NAME'],
			'EMAIL' => $data['BICONNECTOR_KEY_CREATED_USER_EMAIL'],
			'LOGIN' => $data['BICONNECTOR_KEY_CREATED_USER_LOGIN'],
		],
		true
	);

	$fileInfo = \CFile::ResizeImageGet(
		$data['BICONNECTOR_KEY_CREATED_USER_PERSONAL_PHOTO'],
		['width' => 60, 'height' => 60],
		BX_RESIZE_IMAGE_EXACT
	);
	if (is_array($fileInfo) && isset($fileInfo['src']))
	{
		$userEmptyAvatar = '';
		$photoUrl = $fileInfo['src'];
		$userAvatar = " style='background-image: url(\"{$photoUrl}\")'";
	}

	$userNameElement = '<span class="biconnector-key-grid-avatar ui-icon ui-icon-common-user' . $userEmptyAvatar . '">'
		. '<i' . $userAvatar . '></i>'
		. '</span>'
		. '<span class="biconnector-key-grid-username-inner">' . $userName . '</span>';

	$data['CREATED_BY'] = '<div class="biconnector-key-grid-username-wrapper">'
		.'<a class="biconnector-key-grid-username" href="/company/personal/user/' . $data['CREATED_BY'] . '/">' . $userNameElement . '</a>'
		.'</div>';

	$data['DATE_CREATE'] = preg_replace('/([0-9]{2}:[0-9]{2}):[0-9]{2}/', '\\1', $data['DATE_CREATE']);
	$data['APPLICATION'] = htmlspecialcharsEx($data['BICONNECTOR_KEY_APPLICATION_APP_NAME']);

	$actions = [];

	if ($arResult['CAN_WRITE'])
	{
		$url = str_replace('#ID#', urlencode($data['ID']), $arParams['KEY_EDIT_URL']);
		$actions[] = [
			'ID' => 'edit',
			'TEXT' => Loc::getMessage('CC_BBKL_ACTION_MENU_EDIT'),
			'ONCLICK' => 'BX.SidePanel.Instance.open(\'' . CUtil::JSEscape($url) . '\')',
			'DEFAULT' => true,
		];

		$actions[] = [
			'ID' => 'delete',
			'TEXT' => Loc::getMessage('CC_BBKL_ACTION_MENU_DELETE'),
			'ONCLICK' => 'BX.Main.gridManager.getInstanceById(\'' . $arResult['GRID_ID'] . '\').confirmDialog({CONFIRM: true, CONFIRM_MESSAGE: \'' . Loc::getMessage('CC_BBKL_ACTION_MENU_DELETE_CONF') . '\'}, function(){BX.Main.gridManager.getInstanceById(\'' . $arResult['GRID_ID'] . '\').removeRow(\'' . $data['ID'] . '\')})',
		];
	}

	$arResult['ROWS'][] = [
		'id' => $data['ID'],
		'data' => $data,
		'actions' => $actions,
	];
}

$this->IncludeComponentTemplate();
