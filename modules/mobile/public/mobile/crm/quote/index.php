<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$APPLICATION->IncludeComponent("bitrix:mobile.crm.quote.list", "", array(
			"GRID_ID" => "mobile_crm_quote_list",
			"ITEM_PER_PAGE" => 20,
			'QUOTE_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/quote/?page=view&quote_id=#quote_id#',
			'QUOTE_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/quote/?page=edit&quote_id=#quote_id#',
			'QUOTE_CREATE_URL_TEMPLATE' => SITE_DIR.'mobile/crm/quote/?page=edit',
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getQuoteFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_quote_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array("TITLE", "DATE_MODIFY", "STAGE_ID", "ENTITIES_LINKS", "FORMATTED_OPPORTUNITY"),
			"EVENT_NAME" => "onQuoteListFields"
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getQuoteSortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_quote_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onQuoteListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getQuoteFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_quote_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onQuoteListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.quote.edit',
			'',
			array(
				"RESTRICTED_MODE" => false,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'LEAD_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=view&lead_id=#lead_id#',
				'DEAL_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#',
				'PRODUCT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=list&list_mode=selector',
				'CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=contact',
				'COMPANY_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=company',
				'LEAD_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=lead',
				'DEAL_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=deal',
				'QUOTE_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/quote/?page=view&quote_id=#quote_id#',
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.quote.edit',
			'',
			array(
				"RESTRICTED_MODE" => true,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'LEAD_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=view&lead_id=#lead_id#',
				'DEAL_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#',
				'QUOTE_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/quote/?page=edit&quote_id=#quote_id#',
				"ACTIVITY_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#",
				"ACTIVITY_EDIT_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#",
				"EVENT_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/event/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#"
			)
		);
		break;
}
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
