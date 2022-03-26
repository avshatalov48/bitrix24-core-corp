<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$panelItems = array();
$idPrefix = "crm_control_panel_";
$crmPanelContainer = $idPrefix."container";
$menuContainerId = $idPrefix."menu";
$searchContainerId = $idPrefix."search";
$searchInputId = $searchContainerId."_input";

if (!empty($arResult["ITEMS"]) && is_array($arResult["ITEMS"]))
{
	foreach ($arResult["ITEMS"] as $key => $item)
	{
		$itemActions = isset($item["ACTIONS"]) ? [
			"CLASS" => $item["ACTIONS"][0]["ID"] === "CREATE" ? "crm-menu-plus-btn" : "",
			"URL" => $item["ACTIONS"][0]["URL"]
		] : false;

		$panelItem = [
			"TEXT" => $item["TEXT"] ?? $item["NAME"],
			"CLASS" => "crm-menu-".$item["ICON"]." crm-menu-item-wrap",
			"CLASS_SUBMENU_ITEM" => "crm-menu-more-".$item["ICON"],
			"ID" => isset($item["MENU_ID"]) ? $item["MENU_ID"] : $item["ID"],
			"SUB_LINK" => $itemActions,
			"COUNTER" => $item["COUNTER"] > 0 ? $item["COUNTER"] : false,
			"COUNTER_ID" => isset($item["COUNTER_ID"]) ? $item["COUNTER_ID"] : "",
			"IS_ACTIVE" => $arResult["ACTIVE_ITEM_ID"] === $item["ID"],
			"IS_LOCKED" => $item["IS_LOCKED"] ? true : false,
			"IS_DISABLED" => $item["IS_DISABLED"] ? true : false,
			"ITEMS" => !empty($item["ITEMS"]) ? $item["ITEMS"] : false,
		];

		if (isset($item['URL']))
		{
			$panelItem['URL'] = $item['URL'];
		}
		if (isset($item['ON_CLICK']))
		{
			$panelItem['ON_CLICK'] = $item['ON_CLICK'];
		}
		if (isset($item['CLASS']))
		{
			$panelItem['CLASS'] .= ' ' . $item['CLASS'];
		}
		if (isset($item['CLASS_SUBMENU_ITEM']))
		{
			$panelItem['CLASS_SUBMENU_ITEM'] .= ' ' . $item['CLASS_SUBMENU_ITEM'];
		}

		$panelItems[] = $panelItem;
	}
}

$arResult["CRM_PANEL_CONTAINER_ID"] = $crmPanelContainer;
$arResult["CRM_PANEL_MENU_CONTAINER_ID"] = $menuContainerId;
$arResult["CRM_PANEL_SEARCH_CONTAINER_ID"] = $searchContainerId;
$arResult["CRM_PANEL_SEARCH_INPUT_ID"] = $searchInputId;
$arResult["ITEMS"] = $panelItems;