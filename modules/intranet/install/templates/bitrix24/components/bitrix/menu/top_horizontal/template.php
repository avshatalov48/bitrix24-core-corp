<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$this->setFrameMode(true);

global $APPLICATION;

if (!is_array($arResult) || empty($arResult))
	return;

$items = array();

foreach ($arResult as $item)
{
	$newItem = array(
		"TEXT" => $item["TEXT"],
		"URL" => $item["LINK"],
		"ID" => $item["PARAMS"]["menu_item_id"],
		"COUNTER" => isset($item["PARAMS"]["counter_num"]) && intval($item["PARAMS"]["counter_num"]) ? $item["PARAMS"]["counter_num"] : "",
		"IS_ACTIVE" => $item["SELECTED"],
		"IS_LOCKED" => "",
		"CLASS" => $item["PARAMS"]["class"],
	);

	if (isset($item["PARAMS"]["action"]) && is_array($item["PARAMS"]["action"]))
	{
		if (isset($item["PARAMS"]["action"]["ID"]) && $item["PARAMS"]["action"]["ID"] == "CREATE")
		{
			$newItem["SUB_LINK"] = array(
				//"CLASS" => "crm-menu-plus-btn",
				"URL" => $item["PARAMS"]["action"]["URL"],
			);
		}
	}

	$items[] = $newItem;
}

$menuId = "top_panel_menu";

$topMenuSectionDir = $APPLICATION->GetPageProperty("topMenuSectionDir"); //hack for complex component (/company/personal/ pages)
if (!empty($topMenuSectionDir))
{
	$arParams["MENU_DIR"] = $topMenuSectionDir;
}

if (isset($arParams["MENU_DIR"]) && !empty($arParams["MENU_DIR"]))
{
	$menuId = str_replace("/", "_", trim($arParams["MENU_DIR"], "/"));
	$menuId = "top_menu_id_".$menuId;
}

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.buttons",
	"",
	array(
		"ID" => $menuId,
		"ITEMS" => $items
	)
);