<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?><div class="crm_wrapper"><?
$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.product_row.list',
	'',
	array(
		'UID' => 'mobile_crm_product_row_list'
	)
);
?></div><?
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
