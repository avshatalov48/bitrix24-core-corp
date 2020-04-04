<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.invoice_event.list',
	'',
	array(
		'UID' => 'mobile_crm_invoice_event_list'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
