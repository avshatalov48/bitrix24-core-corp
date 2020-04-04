<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$APPLICATION->IncludeComponent("bitrix:mobile.crm.company.list", "", array(
			"GRID_ID" => "mobile_crm_company_list",
			"ITEM_PER_PAGE" => 20,
			"COMPANY_VIEW_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=view&company_id=#company_id#",
			"COMPANY_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=edit&company_id=#company_id#",
			"COMPANY_CREATE_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=edit",
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getCompanyFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_company_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array("TITLE", 'LOGO', 'ASSIGNED_BY', 'COMPANY_TYPE', 'INDUSTRY', 'PHONE', 'EMAIL'),
			"EVENT_NAME" => "onCompanyListFields"
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getCompanySortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_company_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onCompanyListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getCompanyFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_company_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onCompanyListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			"bitrix:mobile.crm.company.edit",
			"",
			array(
				"RESTRICTED_MODE" => false,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR."mobile/users/?user_id=#user_id#",
				"COMPANY_VIEW_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=view&company_id=#company_id#",
				'CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=contact&event=onCrmContactSelectForCompany',
				"CONTACT_SHOW_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=view&contact_id=#contact_id#",
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			"bitrix:mobile.crm.company.edit",
			"",
			array(
				"RESTRICTED_MODE" => true,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR."mobile/users/?user_id=#user_id#",
				"COMPANY_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/company/?page=edit&company_id=#company_id#",
				"CONTACT_SHOW_URL_TEMPLATE" => SITE_DIR."mobile/crm/contact/?page=view&contact_id=#contact_id#",
				"ACTIVITY_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#",
				"ACTIVITY_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#",
				"EVENT_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/event/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#",
				"DEAL_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/deal/?page=list&company_id=#company_id#",
			)
		);
		break;
}
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
