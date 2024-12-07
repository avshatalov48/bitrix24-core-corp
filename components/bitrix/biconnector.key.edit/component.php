<?php
/**
 * @var CMain $APPLICATION
 * @var CUser $USER
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $this
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Services\ApacheSuperset;
use Bitrix\Main\Localization\Loc;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Bitrix\BiConnector\Settings;

if (!$USER->CanDoOperation('biconnector_key_manage'))
{
	ShowError(Loc::getMessage('ACCESS_DENIED'));
	return;
}

if (!\Bitrix\Main\Loader::includeModule('biconnector'))
{
	ShowError(Loc::getMessage('CC_BBKE_ERROR_INCLUDE_MODULE'));
	return;
}

$arResult['ERRORS'] = [];
$arResult['CONNECTIONS'] = \Bitrix\BIConnector\Manager::getInstance()->getConnections();

if (empty($arResult['CONNECTIONS']))
{
	$arResult['ERRORS'][] = Loc::getMessage('CC_BBKE_ERROR_NO_CONNECTION');
}

if (!\Bitrix\BIConnector\LimitManager::getInstance()->checkLimit())
{
		$arResult['ERRORS'][] = Loc::getMessage('CC_BBKE_ERROR_LIMIT_EXCEEDED');
}

if (
	$_SERVER['REQUEST_METHOD'] === 'POST'
	&& check_bitrix_sessid()
	&& $_POST['save'] === 'Y'
)
{
	if (empty($arResult['ERRORS']))
	{
		$data = [
			'ID' => (int)$_POST['ID'],
			'ACTIVE' => $_POST['ACTIVE'] === 'Y',
			'CONNECTION' => $_POST['CONNECTION'],
			'USER_ID' => $USER->GetID(),
		];
		if (isset($_POST['USERS']))
		{
			$data['USERS'] = explode(',', $_POST['USERS']);
		}

		$resultSave = \Bitrix\BIConnector\KeyManager::save($data);

		if ($resultSave instanceof \Bitrix\Main\ErrorCollection)
		{
			foreach ($resultSave->getValues() as $error)
			{
				if ($error instanceof \Bitrix\Main\Error)
				{
					ShowError(
						$error->getCode()
						. ($error->getMessage() !== '' ? ': ' . $error->getMessage() : '')
					);
				}
			}
			return;
		}
		else
		{
			$redirectUrl = str_replace('#ID#', $resultSave, $arParams['KEY_EDIT_URL']);
			if (($_REQUEST['IFRAME'] == 'Y') && ($_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER'))
			{
				$redirectUrl = (new \Bitrix\Main\Web\Uri($redirectUrl))
					->addParams([
						'IFRAME' => 'Y',
						'IFRAME_TYPE' => 'SIDE_SLIDER',
						'sidePanelAction' => 'close',
					])
					->getUri()
				;
			}
			LocalRedirect($redirectUrl);
		}
	}
}

$arResult['GRID_ID'] = 'biconnector_key_list';
$arResult['FORM_ID'] = 'biconnector_key_edit';

$keyList = \Bitrix\BIConnector\KeyTable::getList([
	'select' => [
		'ID',
		'CONNECTION',
		'ACCESS_KEY',
		'ACTIVE',
		'APP_ID',
	],
	'filter' => [
		'=ID' => $arParams['ID'],
	],
]);

$converter = \Bitrix\Main\Text\Converter::getHtmlConverter();
if ($aKey = $keyList->fetch($converter))
{
	$APPLICATION->SetTitle(Loc::getMessage('CC_BBKE_TITLE_EDIT'));
	$arResult['FORM_DATA'] = $aKey;
}
else
{
	$APPLICATION->SetTitle(Loc::getMessage('CC_BBKE_TITLE_CREATE'));
	$arResult['FORM_DATA'] = [
		'ID' => 0,
		'CONNECTION' => key($arResult['CONNECTIONS']),
		'ACCESS_KEY' => \Bitrix\BIConnector\KeyManager::generateAccessKey(),
		'ACTIVE' => 'Y',
		'APP_ID' => 0,
	];
}

$arResult['FORM_DATA']['USERS'] = [];
if ($arResult['FORM_DATA']['ID'] > 0)
{
	$userList = \Bitrix\BIConnector\KeyUserTable::getList([
		'select' => ['ID', 'USER_ID'],
		'filter' => [
			'=KEY_ID' => $arResult['FORM_DATA']['ID'],
			'!=KEY.SERVICE_ID' => ApacheSuperset::getServiceId(),
		]
	]);
	while ($user = $userList->fetch())
	{
		$arResult['FORM_DATA']['USERS'][] = $user['USER_ID'];
	}
}

if ($arResult['ERRORS'])
{
	$arResult['FORM_DATA']['CONNECTION'] = $_POST['CONNECTION'];
	$arResult['FORM_DATA']['ACCESS_KEY'] = $_POST['ACCESS_KEY'];
	$arResult['FORM_DATA']['ACTIVE'] = $_POST['ACTIVE'];
	$arResult['FORM_DATA']['USERS'] = [];
	foreach (explode(',', $_POST['USERS']) as $userId)
	{
		if ($userId > 0)
		{
			$arResult['FORM_DATA']['USERS'][] = $userId;
		}
	}
}

Toolbar::addButton(new Settings\Buttons\Implementation());
Toolbar::deleteFavoriteStar();

$this->includeComponentTemplate();
