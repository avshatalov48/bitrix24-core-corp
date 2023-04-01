<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

$arActivityDescription = [
	'NAME' => Loc::getMessage('CRMBPGQR_DESCR_NAME'),
	'DESCRIPTION' => Loc::getMessage('CRMBPGQR_DESCR_DESCR_1_MSGVER_1'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGenerateQr',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'other',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
			['crm', 'CCrmDocumentLead'],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\Dynamic::class],
			['crm', \Bitrix\Crm\Integration\BizProc\Document\SmartDocument::class],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
		'GROUP' => ['other'],
		'ASSOCIATED_TRIGGERS' => [
			'QR' => 1,
		],
		'SORT' => 3000,
		'IS_SUPPORTING_ROBOT' => true,
	],
	'RETURN' => [
		'PageLink' => [
			'NAME' => GetMessage('CRMBPGQR_RETURN_PAGE_LINK'),
			'TYPE' => 'string',
		],
		'PageLinkBb' => [
			'NAME' => GetMessage('CRMBPGQR_RETURN_PAGE_LINK_BB'),
			'TYPE' => 'string',
		],
		'PageLinkHtml' => [
			'NAME' => GetMessage('CRMBPGQR_RETURN_PAGE_LINK_HTML'),
			'TYPE' => 'string',
		],
		'QrLink' => [
			'NAME' => GetMessage('CRMBPGQR_RETURN_QR_LINK'),
			'TYPE' => 'string',
		],
		'QrLinkBb' => [
			'NAME' => GetMessage('CRMBPGQR_RETURN_QR_LINK_BB'),
			'TYPE' => 'string',
		],
		'QrLinkHtml' => [
			'NAME' => GetMessage('CRMBPGQR_RETURN_QR_LINK_HTML'),
			'TYPE' => 'string',
		],
		'QrImgHtml' => [
			'NAME' => GetMessage('CRMBPGQR_RETURN_QR_IMG'),
			'TYPE' => 'string',
		],
	],
];

//TODO: temporary, skip version control
if (!file_exists(\Bitrix\Main\Application::getDocumentRoot() . '/pub/crm/qr/index.php'))
{
	$arActivityDescription['EXCLUDED'] = true;
}
