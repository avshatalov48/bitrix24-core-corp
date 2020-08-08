<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:main.field.config.detail',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [],
		"USE_PADDING" => false,
		"USE_UI_TOOLBAR" => "Y",
	]
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>