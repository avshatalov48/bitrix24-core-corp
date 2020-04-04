<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:socialnetwork.group_top", $arCurrentValues);

$arParameters = Array(
		"PARAMETERS"=> Array(
			"GROUP_VAR"=>$arComponentProps["PARAMETERS"]["GROUP_VAR"],
			"PATH_TO_GROUP"=>$arComponentProps["PARAMETERS"]["PATH_TO_GROUP"],
			"PATH_TO_GROUP_SEARCH"=>$arComponentProps["PARAMETERS"]["PATH_TO_GROUP_SEARCH"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
		),
		"USER_PARAMETERS"=> Array(
//			"ITEMS_COUNT"=>$arComponentProps["PARAMETERS"]["ITEMS_COUNT"],
			"DATE_TIME_FORMAT"=>$arComponentProps["PARAMETERS"]["DATE_TIME_FORMAT"],
			"DISPLAY_PICTURE"=>$arComponentProps["PARAMETERS"]["DISPLAY_PICTURE"],
			"DISPLAY_DESCRIPTION"=>$arComponentProps["PARAMETERS"]["DISPLAY_DESCRIPTION"],
			"DISPLAY_NUMBER_OF_MEMBERS"=>$arComponentProps["PARAMETERS"]["DISPLAY_NUMBER_OF_MEMBERS"],
			"FILTER_MY"=>$arComponentProps["PARAMETERS"]["FILTER_MY"],
		),
	);

$arParameters["PARAMETERS"]["GROUP_VAR"]["DEFAULT"] = "group_id";
$arParameters["PARAMETERS"]["PATH_TO_GROUP"]["DEFAULT"] = "/workgroups/group/#group_id#/";
$arParameters["PARAMETERS"]["PATH_TO_GROUP_SEARCH"]["DEFAULT"] = "/workgroups/";
//$arParameters["USER_PARAMETERS"]["ITEMS_COUNT"]["DEFAULT"] = "4";

?>
