<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.client_requisite.edit',
	'',
	array(
		'UID' => 'mobile_crm_client_requisite_edit'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
