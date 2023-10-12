<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.dashboard.edit',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'DASHBOARD_LIST_URL' => 'dashboard_list.php',
			'DASHBOARD_EDIT_URL' => 'dashboard_edit.php?dashboard_id=#ID#',
			'ID' => $_REQUEST['dashboard_id'],
		],
		'USE_UI_TOOLBAR' => 'Y',
		'RELOAD_GRID_AFTER_SAVE' => true,
		'CLOSE_AFTER_SAVE' => true,
	]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
