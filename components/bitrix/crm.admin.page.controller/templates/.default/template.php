<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


$this->SetViewTarget("above_pagetitle");

$isSlider = ($_REQUEST['IFRAME'] == 'Y' && $_REQUEST['IFRAME_TYPE'] == 'SIDE_SLIDER');
if (!$isSlider)
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.buttons",
		"",
		array(
			"ID" => $arResult["MENU_ID"],
			"ITEMS" => $arResult["MENU_ITEMS"],
		)
	);
}

$this->EndViewTarget();

if ($arResult["CONNECT_PAGE"])
{
	$APPLICATION->IncludeComponent(
		"bitrix:crm.admin.page.include",
		"",
		array(
			"PAGE_ID" => $arResult["PAGE_ID"],
			"PAGE_PATH" => $arResult["PAGE_PATH"],
			"PAGE_PARAMS" => $arResult["PAGE_PARAMS"],
			"SEF_FOLDER" => $arResult["SEF_FOLDER"],
			"INTERNAL_PAGE" => $arResult["INTERNAL_PAGE"]
		),
		false
	);
}
