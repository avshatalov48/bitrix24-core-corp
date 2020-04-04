<?php
require($_SERVER["DOCUMENT_ROOT"] . "/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$APPLICATION->IncludeComponent("bitrix:mobile.crm.contact.list", "", array(
			"GRID_ID" => "mobile_crm_contact_list",
			"ITEM_PER_PAGE" => 20,
			"CONTACT_VIEW_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=view&contact_id=#contact_id#",
			"CONTACT_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=edit&contact_id=#contact_id#",
			"CONTACT_CREATE_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=edit",
			'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company?page=view&company_id=#company_id#',
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getContactFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_contact_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array("NAME", "TITLE", "ASSIGNED_BY", "POST", "COMPANY_ID", "PHOTO", "PHONE", "EMAIL"),
			"EVENT_NAME" => "onContactListFields"
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getContactSortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_contact_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onContactListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getContactFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_contact_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onContactListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			"bitrix:mobile.crm.contact.edit",
			"",
			array(
				"RESTRICTED_MODE" => false,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR."mobile/users/?user_id=#user_id#",
				"CONTACT_VIEW_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=view&contact_id=#contact_id#",
				"COMPANY_SELECTOR_URL_TEMPLATE" => SITE_DIR.'mobile/crm/entity/?entity=company',
				"COMPANY_SHOW_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=view&company_id=#company_id#",
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			"bitrix:mobile.crm.contact.edit",
			"",
			array(
				"RESTRICTED_MODE" => true,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR."mobile/users/?user_id=#user_id#",
				"CONTACT_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=edit&contact_id=#contact_id#",
				"COMPANY_SHOW_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=view&company_id=#company_id#",
				"ACTIVITY_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#",
				"ACTIVITY_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#",
				"EVENT_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/event/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#",
				"DEAL_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/deal/?page=list&contact_id=#contact_id#",
			)
		);
		break;
}
?>

<?require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
