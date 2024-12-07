<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRM_SEND_EDNA_WHATS_APP_MESSAGE_ACTIVITY_DESCRIPTION_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRM_SEND_EDNA_WHATS_APP_MESSAGE_ACTIVITY_DESCRIPTION_DESCRIPTION'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmSendWhatsAppMessageActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'document',
		'OWN_ID' => 'crm',
		'OWN_NAME' => 'CRM',
	],
	'FILTER' => [
		'MIN_API_VERSION' => 1,
		'INCLUDE' => [
			['crm'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'client',
		'GROUP' => ['clientCommunication'],
		'SORT' => 1349
	],
];

if (
	!defined('CBPRuntime::ACTIVITY_API_VERSION')
	|| !\Bitrix\Main\Loader::includeModule('messageservice')
	|| !\Bitrix\MessageService\Sender\Sms\Ednaru::isSupported()
)
{
	unset($arActivityDescription['FILTER']);
	$arActivityDescription['EXCLUDED'] = true;
}
