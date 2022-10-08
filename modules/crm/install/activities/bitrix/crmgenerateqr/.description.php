<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$arActivityDescription = [
	'NAME' => GetMessage('CRMBPGQR_DESCR_NAME'),
	'DESCRIPTION' => GetMessage('CRMBPGQR_DESCR_DESCR'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGenerateQr',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => [
		'ID' => 'other',
	],
	'FILTER' => [
		'INCLUDE' => [
			['crm', 'CCrmDocumentDeal'],
		],
	],
	'ROBOT_SETTINGS' => [
		'CATEGORY' => 'employee',
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
