<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


$this->SetViewTarget("above_pagetitle");

$iframe = $_REQUEST['IFRAME'] ?? null;
$sideSlider = $_REQUEST['IFRAME_TYPE'] ?? null;
$isSlider = ($iframe === 'Y' && $sideSlider === 'SIDE_SLIDER');
if (!$isSlider)
{
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.buttons",
		"",
		array(
			"ID" => $arResult["MENU_ID"] ?? '',
			"ITEMS" => $arResult["MENU_ITEMS"] ?? [],
		)
	);
}

$this->EndViewTarget();

$isOnlyMenu = isset($arParams['IS_ONLY_MENU']) && $arParams['IS_ONLY_MENU'] === true;

if ($arResult["CONNECT_PAGE"] && !$isOnlyMenu)
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
