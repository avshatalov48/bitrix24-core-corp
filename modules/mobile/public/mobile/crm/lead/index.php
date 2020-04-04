<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$APPLICATION->IncludeComponent("bitrix:mobile.crm.lead.list", "", array(
			"GRID_ID" => "mobile_crm_lead_list",
			"ITEM_PER_PAGE" => 20,
			'LEAD_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=view&lead_id=#lead_id#',
			'LEAD_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=edit&lead_id=#lead_id#',
			'LEAD_CREATE_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=edit',
			'CONTACT_SELECTOR_URL_TEMPLATE_FOR_CONVERT' => SITE_DIR.'mobile/crm/entity/?entity=contact&event=onLeadConvertSelectContact',
			'COMPANY_SELECTOR_URL_TEMPLATE_FOR_CONVERT' => SITE_DIR.'mobile/crm/entity?entity=company&event=onLeadConvertSelectCompany'
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getLeadFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_lead_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array("TITLE", "DATE_CREATE", "STATUS_ID"),
			"EVENT_NAME" => "onLeadListFields",
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getLeadSortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_lead_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onLeadListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getLeadFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_lead_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onLeadListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.lead.edit',
			'',
			array(
				"RESTRICTED_MODE" => false,
				'PRODUCT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=list&list_mode=selector',
				'LEAD_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=view&lead_id=#lead_id#'
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.lead.edit',
			'',
			array(
				"RESTRICTED_MODE" => true,
				'LEAD_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/lead/?page=edit&lead_id=#lead_id#',
				'CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=contact',
				'COMPANY_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/entity/?entity=company',
				'ACTIVITY_LIST_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#',
				'ACTIVITY_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/edit.php?owner_type=#owner_type#&owner_id=#owner_id#&type_id=#type_id#',
				'COMMUNICATION_LIST_URL_TEMPLATE' => SITE_DIR.'mobile/crm/comm/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#&type_id=#type_id#',
				'EVENT_LIST_URL_TEMPLATE' => SITE_DIR.'mobile/crm/event/list.php?entity_type_id=#entity_type_id#&entity_id=#entity_id#',
			)
		);
		break;
}
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
