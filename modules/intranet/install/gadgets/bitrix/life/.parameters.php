<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:news.list", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"IBLOCK_TYPE"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_TYPE"],
			"IBLOCK_ID"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_ID"],
			"ACTIVE_DATE_FORMAT" 	=>	$arComponentProps["PARAMETERS"]["ACTIVE_DATE_FORMAT"],
			"LIST_URL"	=> Array(
				"NAME" => GetMessage("GD_LIFE_P_LIST_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/about/life.php",
			),
			"DETAIL_URL"	=> Array(
				"NAME" => GetMessage("GD_LIFE_P_DETAIL_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/about/life.php?ID=#ELEMENT_ID#",
			),
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
		),
		"USER_PARAMETERS"=> Array(
			"NEWS_COUNT"	=>	$arComponentProps["PARAMETERS"]["NEWS_COUNT"],
			"DISPLAY_DATE"	=>	Array(
					"NAME" => GetMessage("GD_LIFE_P_SHOW_DATE"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
				),
			"DISPLAY_PICTURE"	=>	Array(
					"NAME" => GetMessage("GD_LIFE_P_SHOW_PIC"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
				),
			"DISPLAY_PREVIEW_TEXT"	=>	Array(
					"NAME" => GetMessage("GD_LIFE_P_SHOW_PREVIEW"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
				),
		),
	);
$arParameters["USER_PARAMETERS"]["NEWS_COUNT"]["DEFAULT"] = 5;
?>
