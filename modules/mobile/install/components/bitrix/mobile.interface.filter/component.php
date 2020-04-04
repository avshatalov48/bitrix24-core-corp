<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

$gridId = $arParams["GRID_ID"];
$gridOptions = CUserOptions::GetOption("mobile.interface.grid", $gridId);

$arResult["CURRENT_FILTER_FIELDS"] = isset($gridOptions["filters"]["filter_user"]["fields"]) ? $gridOptions["filters"]["filter_user"]["fields"] : array();
$arResult["CURRENT_FILTER_ROWS"] = isset($gridOptions["filters"]["filter_user"]["filter_rows"]) ? $gridOptions["filters"]["filter_user"]["filter_rows"] : array();

$arResult['FIELDS'] = array();
if (isset($arParams["FIELDS"]) && is_array($arParams["FIELDS"]))
	$arResult['FIELDS'] = $arParams["FIELDS"];

$arResult['EVENT_NAME'] = $arParams["EVENT_NAME"];

$arResult["FIELDS_ID"] = array();
foreach($arResult['FIELDS'] as $key => $field)
{
	if (in_array($field["id"], $arResult["CURRENT_FILTER_ROWS"]))
	{
		if ($field["type"] == "select-user")
		{
			$arResult['FIELDS'][$key]["item"] = CMobileHelper::getUserInfo($arResult["CURRENT_FILTER_FIELDS"][$field["id"]]);
		}
		elseif ($field["type"] == "checkbox")
		{
			$arResult['FIELDS'][$key]["value"] = $arResult["CURRENT_FILTER_FIELDS"][$field["id"]] == "Y" ? "Y" : "N";
		}
		else
		{
			$arResult['FIELDS'][$key]["value"] = $arResult["CURRENT_FILTER_FIELDS"][$field["id"]];
		}
	}

	if ($field["type"] == "number")
	{
		$arResult['FIELDS'][$key]["item"]["from"] = $arResult["CURRENT_FILTER_FIELDS"][$field["id"]."_from"];
		$arResult['FIELDS'][$key]["item"]["to"] = $arResult["CURRENT_FILTER_FIELDS"][$field["id"]."_to"];

		$arResult["FIELDS_ID"][$field["id"]."_from"] = $field["id"]."_from";
		$arResult["FIELDS_ID"][$field["id"]."_to"] = $field["id"]."_to";

	}
	else
		$arResult["FIELDS_ID"][$field["id"]] = $field["id"];
}

$this->IncludeComponentTemplate();
?>
