<?php
// file for compatibility, need to delete after for a while
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$APPLICATION->IncludeComponent(
	"bitrix:mobile.crm.company.edit",
	"",
	array(
		"RESTRICTED_MODE" => true,
		"USER_PROFILE_URL_TEMPLATE" => SITE_DIR."mobile/users/?user_id=#user_id#",
		"COMPANY_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=edit&company_id=#company_id#",
		"CONTACT_SHOW_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=view&contact_id=#contact_id#",
	)
);
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
