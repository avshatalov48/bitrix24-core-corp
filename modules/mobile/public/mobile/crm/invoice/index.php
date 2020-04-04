<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$APPLICATION->IncludeComponent("bitrix:mobile.crm.invoice.list", "", array(
			"GRID_ID" => "mobile_crm_invoice_list",
			"ITEM_PER_PAGE" => 20,
			'INVOICE_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/invoice/?page=view&invoice_id=#invoice_id#',
			'INVOICE_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/invoice/?page=edit&invoice_id=#invoice_id#',
			'INVOICE_CREATE_URL_TEMPLATE' => SITE_DIR.'mobile/crm/invoice/?page=edit',
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getInvoiceFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_invoice_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array("TITLE", "STATUS_ID", "FORMATTED_PRICE", "ENTITIES_LINKS", "RESPONSIBLE", "ACCOUNT_NUMBER"),
			"EVENT_NAME" => "onInvoiceListFields"
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getInvoiceSortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_invoice_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onInvoiceListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getInvoiceFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_invoice_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onInvoiceListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.invoice.edit',
			'',
			array(
				"RESTRICTED_MODE" => false,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'DEAL_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#',
				'QUOTE_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/quote/?page=view&quote_id=#quote_id#',
				'PRODUCT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=list&list_mode=selector',
				'CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=contact',
				'CLIENT_CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=contact',
				'CLIENT_COMPANY_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=company',
				'QUOTE_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=quote',
				'DEAL_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=deal',
				'INVOICE_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/invoice/?page=view&invoice_id=#invoice_id#',
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.invoice.edit',
			'',
			array(
				"RESTRICTED_MODE" => true,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'INVOICE_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/invoice/?page=edit&invoice_id=#invoice_id#',
				'DEAL_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/deal/?page=view&deal_id=#deal_id#',
				'QUOTE_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/quote/?page=view&quote_id=#quote_id#',
				'ACTIVITY_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#',
				"ACTIVITY_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/activity/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#",
				"EVENT_LIST_URL_TEMPLATE" => SITE_DIR."mobile/crm/event/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#"
			)
		);
		break;
}
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
