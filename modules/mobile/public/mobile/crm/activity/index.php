<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$APPLICATION->IncludeComponent('bitrix:mobile.crm.activity.list', '', array(
			'GRID_ID' => 'mobile_crm_activity_list',
			"ITEM_PER_PAGE" => 20,
			'ACTIVITY_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/?page=view&activity_id=#activity_id#',
			'ACTIVITY_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/?page=edit&activity_id=#activity_id#',
			'ACTIVITY_CREATE_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/?page=edit',

			'TASK_SHOW_URL_TEMPLATE' => '#SITE_DIR#mobile/tasks/snmrouter/index.php?routePage=view&USER_ID=#user_id#&TASK_ID=#task_id#',
			'USER_PROFILE_URL_TEMPLATE' => '#SITE_DIR#mobile/users/?user_id=#user_id#'
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getActivityFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_activity_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array("NAME", "TITLE", "DATE_MODIFY"),
			"EVENT_NAME" => "onActivityListFields"
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getActivitySortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_activity_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onActivityListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getActivityFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_activity_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onActivityListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.activity.edit',
			'',
			array(
				"RESTRICTED_MODE" => false,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'PRODUCT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=list&list_mode=selector',
				'CONTACT_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/client/list.php?entity=contact',
				'COMPANY_SELECTOR_URL_TEMPLATE' => SITE_DIR.'mobile/crm/client/list.php?entity=company',
				'LEAD_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/view.php?activity_id=#activity_id#',
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.activity.edit',
			'',
			array(
				"RESTRICTED_MODE" => true,
				"USER_PROFILE_URL_TEMPLATE" => SITE_DIR.'mobile/users/?user_id=#user_id#',
				'COMPANY_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/company/?page=view&company_id=#company_id#',
				'CONTACT_SHOW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/contact/?page=view&contact_id=#contact_id#',
				'LEAD_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/activity/edit.php?activity_id=#activity_id#',
			)
		);
		break;
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
