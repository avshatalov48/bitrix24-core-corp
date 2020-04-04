<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/report/analytics/index.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_after.php");

if (\Bitrix\Main\Loader::includeModule('report') && !\Bitrix\Report\VisualConstructor\Helper\Analytic::isEnable())
{
	echo 'Analytics is not enabled.';
	die;
}

?>


<?php
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:report.analytics.base',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'PAGE_TITLE' => GetMessage('REPORT_CRM_ANALYTICS_PAGE_TITLE')
		],
		//'PLAIN_VIEW' => true,
		'USE_PADDING' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL'=>'/crm/',
	]
);

?>
