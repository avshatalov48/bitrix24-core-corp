<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
?><div class="crm_wrapper"><?
$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.currency.list',
	'',
	array('UID' => 'mobile_crm_currency_list')
);
?></div><?
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
