<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.product.file",
	".default",
	array(
		"CATALOG_ID" => $arResult['CATALOG_ID'],
		"PRODUCT_ID" => $arResult['PRODUCT_ID'],
		"FIELD_ID" => $arResult['FIELD_ID'],
		"FILE_ID" => $arResult['FILE_ID'],
	),
	$component
);?>