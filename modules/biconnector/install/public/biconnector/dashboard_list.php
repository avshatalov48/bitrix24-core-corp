<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.dashboard.list',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'DASHBOARD_LIST_URL' => 'dashboard_list.php',
			'DASHBOARD_ADD_URL' => 'dashboard_edit.php',
			'DASHBOARD_EDIT_URL' => 'dashboard_edit.php?dashboard_id=#ID#',
			'DASHBOARD_VIEW_URL' => 'dashboard.php?id=#ID#',
		],
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
