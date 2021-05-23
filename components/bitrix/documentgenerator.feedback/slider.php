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

$APPLICATION->includeComponent(
	'bitrix:documentgenerator.feedback', '',
	[
		'provider' => $request->get('provider'),
		'templateName' => $request->get('templateName'),
		'templateCode' => $request->get('templateCode'),
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');