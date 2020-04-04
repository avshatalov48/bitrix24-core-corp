<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.event.list',
	'',
	array(
		'UID' => 'mobile_crm_event_list'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
