<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.product_row.edit',
	'',
	array(
		'SERVICE_URL_TEMPLATE' => '#SITE_DIR#mobile/ajax.php?mobile_action=crm_product_row_edit&site_id=#SITE#&#SID#',
		'UID' => 'mobile_crm_product_row_edit'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
