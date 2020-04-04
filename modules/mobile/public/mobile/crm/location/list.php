<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.location.list',
	'',
	array(
		'UID' => 'mobile_crm_location_list',
		'SERVICE_URL'=> SITE_DIR . 'mobile/ajax.php?mobile_action=crm_location_list&siteID=' . SITE_ID . '&' . bitrix_sessid_get()
	)
);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
