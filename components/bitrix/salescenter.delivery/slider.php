<?php

$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if (($showChecks = $request->get("show_delivery_list")) && $showChecks = 'y')
{
	$pageParams = [
		'lang' => LANGUAGE_ID,
		'publicSidePanel' => 'Y',
		'CLASS_NAME' => $request->get("CLASS_NAME"),
		'SERVICE_TYPE' => $request->get("SERVICE_TYPE"),
	];
	$APPLICATION->IncludeComponent(
		"bitrix:salescenter.page.include",
		"",
		array(
			"PAGE_PATH" => '/shop/settings/sale_delivery_service_list.php',
			"PAGE_PARAMS" => http_build_query($pageParams),
			"SEF_FOLDER" => '/shop/settings/',
			"INTERNAL_PAGE" => 'Y',
		),
		false
	);
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.delivery',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'SEF_FOLDER' => '/shop/settings/'
			]
		]
	);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');