<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Localization\Loc;

use Bitrix\Im\Dialog;

use Bitrix\ImOpenLines\Session\Common;
use Bitrix\ImOpenLines\Model\SessionTable;

Loc::loadMessages(__FILE__);

/** @global \CMain $APPLICATION */
if ($APPLICATION instanceof \CMain)
{
	$APPLICATION->RestartBuffer();
}

if (
	!Loader::includeModule('imopenlines')
	|| !Loader::includeModule('im')
)
{
	\Bitrix\Main\Application::getInstance()->terminate();
}

$errorOutput = function ($message)
{
	$style = "
		font-family: 'Helvetica Neue', Helvetica, sans-serif;
		font-size: 14px;
		padding: 10 12px;
		display: block;
		background-color: #e8f7fe;
		border: 1px solid #e8f7fe;
		border-radius: 4px;
	";

	echo '<span style="'.$style.'">'.$message.'</span>';
};

$error = '';
$getRequest = Context::getCurrent()->getRequest()->toArray();
$check = parse_url($getRequest['DOMAIN']);
if (!in_array($check['scheme'], ['http', 'https']) || empty($check['host']))
{
	$errorOutput(Loc::getMessage('IMOP_QUICK_IFRAME_ERROR_ADDRESS'));
	die();
}
$params = [];

$params['DOMAIN'] = $check['scheme'].'://'.$check['host'];
$params['SERVER_NAME'] = $check['host'];

if (
	isset($_SERVER['HTTP_REFERER'])
	&& !empty($_SERVER['HTTP_REFERER'])
	&& mb_strpos($_SERVER['HTTP_REFERER'], $params['DOMAIN']) !== 0
)
{
	$errorOutput(Loc::getMessage('IMOP_QUICK_IFRAME_ERROR_SECURITY'));
	die();
}

$lineId = null;
$dialogId = $getRequest['DIALOG_ID'] ?? null;
$userCode = $getRequest['DIALOG_ENTITY_ID'] ?? '';
if ($dialogId)
{
	$chatId = Dialog::getChatId($dialogId);
	if ($chatId)
	{
		$sessionData = SessionTable::getList([
			'select' => ['CONFIG_ID'],
			'filter' => [
				'=CHAT_ID' => $chatId,
			],
			'order' => [
				'ID' => 'DESC',
			],
			'limit' => 1,
		])->fetch();
		if ($sessionData)
		{
			$lineId = $sessionData['CONFIG_ID'];
		}
	}
}

if (!$lineId)
{
	$lineId = Common::parseUserCode($userCode)['CONFIG_ID'];
}

$listsDataManager = new \Bitrix\ImOpenlines\QuickAnswers\ListsDataManager($lineId);
$iblockId = $listsDataManager->getIblockId();
$params['CAN_CREATE'] = \CIBlockRights::UserHasRightTo($iblockId, $iblockId, 'section_element_bind');
$params['IMOP_ID'] = $lineId;
$params['DARK_MODE'] = (isset($getRequest['DARK_MODE']) && $getRequest['DARK_MODE'] === 'Y') ? 'Y' : 'N';

$APPLICATION->IncludeComponent('bitrix:imopenlines.iframe.quick', '.default', $params, false, ['HIDE_ICONS' => 'Y']);

\CMain::FinalActions();
die();