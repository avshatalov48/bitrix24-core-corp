<?php

$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

/** @global \CMain $APPLICATION */
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.document.view',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'TEMPLATE_ID' => $request->get('templateId'),
			'VALUE' => $request->get('value'),
			'PROVIDER' => $request->get('providerClassName'),
			'VALUES' => $request->get('values'),
			'DOCUMENT_ID' => $request->get('documentId'),
			'MODE' => $request->get('mode'),
			'NUMBER' => $request->get('number'),
		],
		'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => false,
		'USE_BACKGROUND_CONTENT' => false,
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
