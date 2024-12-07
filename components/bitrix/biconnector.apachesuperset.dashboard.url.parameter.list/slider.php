<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.apachesuperset.dashboard.url.parameter.list',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [],

		'CLOSE_AFTER_SAVE' => false,
		'RELOAD_GRID_AFTER_SAVE' => false,
		'IS_TOOL_PANEL_ALWAYS_VISIBLE' => true,
		'ENABLE_MODE_TOGGLE' => false,

		'USE_BACKGROUND_CONTENT' => true,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	],
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
