<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.comm.list',
	'',
	array(
		'UID' => 'mobile_crm_comm_list',
		'CONTACT_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/contact/?page=view&contact_id=#contact_id#',
		'COMPANY_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/crm/company/?page=view&company_id=#company_id#'
	)
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
