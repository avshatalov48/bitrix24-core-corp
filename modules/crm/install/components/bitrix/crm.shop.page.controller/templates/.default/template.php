<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

Bitrix\Main\Page\Asset::getInstance()->addJs("/bitrix/js/main/hot_keys.js");

$selfFolderUrl = "/shop/settings/";

$APPLICATION->IncludeComponent(
	"bitrix:crm.admin.page.controller",
	"",
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => $selfFolderUrl,
		"MENU_ID" => "store",
		"PATH_TO_BASE_PAGE" => "",
		"ADDITIONAL_PARAMS" => $arResult["ADDITIONAL_PARAMS"],
		"CONNECT_PAGE" => $arResult["CONNECT_PAGE"],
		"INTERNAL_PAGE_LIST" => array(
			"iblock_element_edit" => $selfFolderUrl."iblock_element_edit.php",
			"cat_product_edit" => $selfFolderUrl."cat_product_edit.php",
			"cat_section_edit" => $selfFolderUrl."cat_section_edit.php",
			"userfield_edit" => $selfFolderUrl."userfield_edit.php",
			"iblock_edit_property" => $selfFolderUrl."iblock_edit_property.php",
			"cat_catalog_edit" => $selfFolderUrl."cat_catalog_edit.php",
			"sale_cashbox_edit" => $selfFolderUrl."sale_cashbox_edit.php",
			"sale_buyers_profile" => $selfFolderUrl."sale_buyers_profile.php",
			"sale_buyers_profile_edit" => $selfFolderUrl."sale_buyers_profile_edit.php",
			"sale_account_edit" => $selfFolderUrl."sale_account_edit.php",
			"sale_transact_edit" => $selfFolderUrl."sale_transact_edit.php",
			"cat_store_document_edit" => $selfFolderUrl."cat_store_document_edit.php",
			"cat_contractor_edit" => $selfFolderUrl."cat_contractor_edit.php",
			"cat_store_edit" => $selfFolderUrl."cat_store_edit.php",
			"sale_discount_edit" => $selfFolderUrl."sale_discount_edit.php",
			"sale_discount_preset_detail" => $selfFolderUrl."sale_discount_preset_detail.php",
			"sale_discount_coupon_edit" => $selfFolderUrl."sale_discount_coupon_edit.php",
			"sale_delivery_service_edit" => $selfFolderUrl."sale_delivery_service_edit.php",
			"sale_delivery_eservice_edit" => $selfFolderUrl."sale_delivery_eservice_edit.php",
			"sale_pay_system_edit" => $selfFolderUrl."sale_pay_system_edit.php",
			"sale_yandexinvoice_settings" => $selfFolderUrl."sale_yandexinvoice_settings.php",
			"sale_person_type_edit" => $selfFolderUrl."sale_person_type_edit.php",
			"sale_tax_edit" => $selfFolderUrl."sale_tax_edit.php",
			"sale_tax_rate_edit" => $selfFolderUrl."sale_tax_rate_edit.php",
			"cat_vat_edit" => $selfFolderUrl."cat_vat_edit.php",
			"sale_tax_exempt_edit" => $selfFolderUrl."sale_tax_exempt_edit.php",
			"cat_measure_edit" => $selfFolderUrl."cat_measure_edit.php",
			"cat_group_edit" => $selfFolderUrl."cat_group_edit.php",
			"cat_round_edit" => $selfFolderUrl."cat_round_edit.php",
			"cat_extra_edit" => $selfFolderUrl."cat_extra_edit.php",
			"sale_location_node_edit" => $selfFolderUrl."sale_location_node_edit.php",
			"sale_location_reindex" => $selfFolderUrl."sale_location_reindex.php",
			"sale_location_group_edit" => $selfFolderUrl."sale_location_group_edit.php",
			"sale_location_type_edit" => $selfFolderUrl."sale_location_type_edit.php",
			"sale_location_import" => $selfFolderUrl."sale_location_import.php",
			"iblock_subelement_edit" => $selfFolderUrl."iblock_subelement_edit.php",
			"report_view" => $selfFolderUrl."sale_report_view.php",
			"permissions" => $selfFolderUrl."/permissions/index.php",
		),
		"SIDE_PANEL_PAGE_LIST" => array(
			"sale_location_import",
			"sale_location_reindex",
			"menu_sale_bizval",
			"sale_business_value_ptypes",
			"menu_catalog_edit",
			"form_order_settings",
		),
	)
);
?>

<script>
	BX.ready(function() {
		new BX.Sale.ShopPublic();
	});
</script>
