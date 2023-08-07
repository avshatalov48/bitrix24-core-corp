<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/report/analytics/index.php");

if (!\Bitrix\Main\Loader::includeModule('report'))
{
	echo 'Analytics is not enabled.';
}
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:report.analytics.base',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'PAGE_TITLE' => GetMessage('REPORT_CRM_ANALYTICS_PAGE_TITLE'),
				'REPORT_GROUPS' => [
					'crm_general',
					'sales_general',
					'catalog_general',
				],
			],
			//'PLAIN_VIEW' => true,
			'USE_PADDING' => false,
			'PAGE_MODE' => false,
			'PAGE_MODE_OFF_BACK_URL' => '/crm/',
		]
	);
}
?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
