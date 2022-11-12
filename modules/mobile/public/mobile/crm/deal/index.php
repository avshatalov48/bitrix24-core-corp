<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$APPLICATION->IncludeComponent("bitrix:mobile.crm.deal.list", "", array(
			"GRID_ID" => "mobile_crm_deal_list",
			"ITEM_PER_PAGE" => 20,
			'DEAL_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#',
			'DEAL_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=edit&deal_id=#deal_id#',
			'DEAL_CREATE_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=edit',
			'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
			'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getDealFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_deal_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array('TITLE', 'STAGE_ID', 'CONTACT', 'COMPANY', 'DATE_MODIFY', 'FORMATTED_OPPORTUNITY'),
			"EVENT_NAME" => "onDealListFields"
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getDealSortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_deal_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onDealListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getDealFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_deal_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onDealListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.deal.edit',
			'',
			array(
				"RESTRICTED_MODE" => false,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'PRODUCT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=list&list_mode=selector',
				'CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=contact&event=onCrmContactSelectForDeal',
				'COMPANY_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=company&event=onCrmCompanySelectForDeal',
				'DEAL_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#',
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.deal.edit',
			'',
			array(
				"RESTRICTED_MODE" => true,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'DEAL_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=edit&deal_id=#deal_id#',
				"ACTIVITY_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#",
				"ACTIVITY_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#",
				"EVENT_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/event/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#"
			)
		);
		break;
}
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
