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

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.userconsent',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'SALESCENTER_DIR' => '/saleshub/',
			'AGREEMENT_MESSAGE' => 'Y',
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');