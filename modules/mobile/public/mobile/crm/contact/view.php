<?php
// file for compatibility, need to delete after for a while
require($_SERVER["DOCUMENT_ROOT"] . "/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

if (!CModule::IncludeModule("crm"))
	return;

$APPLICATION->IncludeComponent(
	"bitrix:mobile.crm.contact.edit",
	"",
	array(
		"RESTRICTED_MODE" => true,
		"USER_PROFILE_URL_TEMPLATE" => SITE_DIR."mobile/users/?user_id=#user_id#",
		"CONTACT_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=edit&contact_id=#contact_id#",
		"COMPANY_SHOW_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=view&company_id=#company_id#",
	)
);
?>

<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
