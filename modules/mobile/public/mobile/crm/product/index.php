<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

if (!CModule::IncludeModule("crm"))
	return;

$page = (isset($_GET["page"])) ? $_GET["page"] : "list";

switch ($page)
{
	case "list":
		$event = (isset($_GET["event"])) ? $_GET["event"] : "";
		$pageIdProductSelectorBack = (isset($_GET["pageIdProductSelectorBack"])) ? $_GET["pageIdProductSelectorBack"] : "";

		$APPLICATION->IncludeComponent("bitrix:mobile.crm.product.list", "", array(
			"GRID_ID" => "mobile_crm_product_list",
			"ITEM_PER_PAGE" => 20,
			'PRODUCT_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=edit&product_id=#product_id#',
			'PRODUCT_CREATE_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=edit',
			"ON_PRODUCT_SELECT_EVENT_NAME" => $event,
			"PAGEID_PRODUCT_SELECTOR_BACK" => $pageIdProductSelectorBack
		));
		break;
	case "fields":
		$fields = CCrmMobileHelper::getProductFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.fields", "", array(
			"GRID_ID" => "mobile_crm_product_list",
			"ALL_FIELDS" => $fields,
			"SELECTED_FIELDS" => array("NAME", "PRICE", "DETAIL_PICTURE", "MEASURE"),
			"EVENT_NAME" => "onProductListFields"
		));
		break;
	case "sort":
		$sortFields = CCrmMobileHelper::getProductSortFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.sort", "", array(
			"GRID_ID" => "mobile_crm_product_list",
			"SORT_FIELDS" => $sortFields,
			"EVENT_NAME" => "onProductListSort"
		));
		break;
	case "filter":
		$filterFields = CCrmMobileHelper::getProductFilterFields();
		$APPLICATION->IncludeComponent("bitrix:mobile.interface.filter", "", array(
			"GRID_ID" => "mobile_crm_product_list",
			"FIELDS" => $filterFields,
			"EVENT_NAME" => "onProductListFilter"
		));
		break;
	case "edit":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.product.edit',
			'',
			array(
				"RESTRICTED_MODE" => false,
				'PRODUCT_VIEW_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=view&product_id=#product_id#',
				'PRODUCT_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=edit&product_id=#product_id#'
			)
		);
		break;
	case "view":
		$APPLICATION->IncludeComponent(
			'bitrix:mobile.crm.product.edit',
			'',
			array(
				"RESTRICTED_MODE" => true,
				'PRODUCT_EDIT_URL_TEMPLATE' => SITE_DIR.'mobile/crm/product/?page=edit&product_id=#product_id#',
			)
		);
		break;
}
?>

<?require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
