<?php
// file for compatibility, need to delete after for a while
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$APPLICATION->IncludeComponent(
	'bitrix:mobile.crm.lead.edit',
	'',
	array(
		"RESTRICTED_MODE" => true,
		'LEAD_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=edit&lead_id=#lead_id#',
		'CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=contact',
		'COMPANY_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=company'
	)
);
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
