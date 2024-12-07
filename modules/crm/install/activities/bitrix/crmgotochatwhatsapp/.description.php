<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


$arActivityDescription = [
	'NAME' => Loc::getMessage('BP_CRM_GO_TO_CHAT_WHATSAPP_NAME'),
	'DESCRIPTION' => Loc::getMessage('BP_CRM_GO_TO_CHAT_WHATSAPP_DESCRIPTION'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGoToChatWhatsApp',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'client',
		'GROUP' => ['clientCommunication'],
		'SORT' => 1350,
	],
];


if (Loader::includeModule('crm'))
{
	$canUseNotifications = \Bitrix\Crm\Integration\NotificationsManager::canUse();
	$isBox = \Bitrix\Crm\Settings\Crm::isBox();

	if (!$canUseNotifications || $isBox)
	{
		$arActivityDescription['EXCLUDED'] = true;
	}
}