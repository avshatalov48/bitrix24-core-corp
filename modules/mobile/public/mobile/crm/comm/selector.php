<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.comm.selector',
	'',
	array(
		'UID' => 'mobile_crm_comm_selector'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
