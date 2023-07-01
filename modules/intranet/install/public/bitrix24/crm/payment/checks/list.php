<?php

use Bitrix\Main\Config\Option;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:crm.order.check.list',
		'POPUP_COMPONENT_TEMPLATE_NAME' => '',
		'POPUP_COMPONENT_PARAMS' => [
			'SET_TITLE' => true,
			'ENABLE_TOOLBAR' => false,
			'CHECK_COUNT' => '20',
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
			'OWNER_ID' => $_REQUEST['owner_id'] ?? '',
			'OWNER_TYPE' => $_REQUEST['owner_type'] ?? '',
			'PATH_TO_ORDER_CHECK_SHOW' => Option::get('crm', 'path_to_order_check_details'),
			'PATH_TO_ORDER_CHECK_EDIT' => Option::get('crm', 'path_to_order_check_details') . '?init_mode=edit',
			'PATH_TO_ORDER_CHECK_DELETE' => Option::get('crm', 'path_to_order_check_details') . '?action=delete',
			'GRID_ID_SUFFIX' => 'CHECK_DETAILS',
			'NAME_TEMPLATE' => CSite::GetNameFormat(false)
		],
		'USE_PADDING' => true,
	]
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');