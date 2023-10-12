<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:biconnector.key.edit',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'KEY_LIST_URL' => 'key_list.php',
			'KEY_EDIT_URL' => 'key_edit.php?key_id=#ID#',
			'ID' => $_REQUEST['key_id'],
		],
		'RELOAD_GRID_AFTER_SAVE' => true,
		'CLOSE_AFTER_SAVE' => true,
		'USE_UI_TOOLBAR' => 'Y',
	]
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
