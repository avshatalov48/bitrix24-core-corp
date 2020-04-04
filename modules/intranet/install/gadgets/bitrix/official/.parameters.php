<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:news.list", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"IBLOCK_TYPE"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_TYPE"],
			"IBLOCK_ID"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_ID"],
			"LIST_URL"	=> Array(
				"NAME" => GetMessage("GD_OFFICIAL_URL_ALL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/about/index.php",
			),
			"DETAIL_URL"	=> Array(
				"NAME" => GetMessage("GD_OFFICIAL_URL_DETAIL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/about/official.php?ID=#ELEMENT_ID#",
			),
			"ACTIVE_DATE_FORMAT" 	=>	$arComponentProps["PARAMETERS"]["ACTIVE_DATE_FORMAT"],
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
		),
		"USER_PARAMETERS"=> Array(
			"NEWS_COUNT"	=>	$arComponentProps["PARAMETERS"]["NEWS_COUNT"],
			"DISPLAY_PREVIEW_TEXT"	=>	Array(
					"NAME" => GetMessage("GD_OFFICIAL_SHOW_PREV"),
					"TYPE" => "CHECKBOX",
					"DEFAULT" => "Y",
				),
		),
	);
$arParameters["USER_PARAMETERS"]["NEWS_COUNT"]["DEFAULT"] = 5;
?>
