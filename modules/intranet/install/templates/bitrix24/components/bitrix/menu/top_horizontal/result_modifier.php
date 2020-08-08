<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!is_array($arResult) || empty($arResult))
	return;

foreach ($arResult as &$item)
{
	//id to item
	if (!isset($item["PARAMS"]["menu_item_id"]) && $item["PARAMS"]["menu_item_id"] == '')
	{
		$item["PARAMS"]["menu_item_id"] = ($item["PARAMS"]["name"] == "live_feed") ? "menu_live_feed" : crc32($item["LINK"]);
	}

	$item["PARAMS"]["class"] = isset($item["PARAMS"]["class"]) ? $item["PARAMS"]["class"] : "";
}
?>