<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:iblock.tv", $arCurrentValues);
$arParameters = Array(
		"PARAMETERS"=> Array(
			"IBLOCK_TYPE"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_TYPE"],
			"IBLOCK_ID"	=>	$arComponentProps["PARAMETERS"]["IBLOCK_ID"],
			"LIST_URL"	=> Array(
				"NAME" => GetMessage("GD_VIDEO_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/about/media.php",
			),
			"PATH_TO_FILE" => $arComponentProps["PARAMETERS"]["PATH_TO_FILE"],
			"DURATION" => $arComponentProps["PARAMETERS"]["DURATION"],
			"SECTION_ID" => $arComponentProps["PARAMETERS"]["SECTION_ID"],
			"ELEMENT_ID" => $arComponentProps["PARAMETERS"]["ELEMENT_ID"],
			"WIDTH" => $arComponentProps["PARAMETERS"]["WIDTH"],
			"HEIGHT" => $arComponentProps["PARAMETERS"]["HEIGHT"],
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
		),
		"USER_PARAMETERS"=> Array(
		),
	);

if(!$arComponentProps["PARAMETERS"]["PATH_TO_FILE"])
	unset($arParameters["PARAMETERS"]["PATH_TO_FILE"]);

if(!$arComponentProps["PARAMETERS"]["SECTION_ID"])
	unset($arParameters["PARAMETERS"]["SECTION_ID"]);

if(!$arComponentProps["PARAMETERS"]["ELEMENT_ID"])
	unset($arParameters["PARAMETERS"]["ELEMENT_ID"]);

if(!$arComponentProps["PARAMETERS"]["DURATION"])
	unset($arParameters["PARAMETERS"]["DURATION"]);

if(!$arComponentProps["PARAMETERS"]["WIDTH"])
	unset($arParameters["PARAMETERS"]["WIDTH"]);

if(!$arComponentProps["PARAMETERS"]["HEIGHT"])
	unset($arParameters["PARAMETERS"]["HEIGHT"]);
?>
