<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.apachesuperset.dashboard.setting',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'DASHBOARD_ID' => $request->get('DASHBOARD_ID'),
		],

		'CLOSE_AFTER_SAVE' => true,
		'RELOAD_GRID_AFTER_SAVE' => true,
		'IS_TOOL_PANEL_ALWAYS_VISIBLE' => true,
		'ENABLE_MODE_TOGGLE' => false,

		'USE_BACKGROUND_CONTENT' => false,
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
