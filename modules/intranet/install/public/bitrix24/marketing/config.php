<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sender.config.limits',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [],
		'USE_UI_TOOLBAR' => 'Y',
		'USE_PADDING' => false,
		'PLAIN_VIEW' => false,
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => "/marketing/"
	]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");