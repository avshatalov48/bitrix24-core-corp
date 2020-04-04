<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:voting.current", $arCurrentValues);

$arParameters = Array(
		"PARAMETERS"=> Array(
			"CHANNEL_SID"=>$arComponentProps["PARAMETERS"]["CHANNEL_SID"],
			"CACHE_TYPE"=>$arComponentProps["PARAMETERS"]["CACHE_TYPE"],
			"CACHE_TIME"=>$arComponentProps["PARAMETERS"]["CACHE_TIME"],
			"LIST_URL"=> Array(
				"NAME" => GetMessage("GD_VOTE_P_URL"),
				"TYPE" => "STRING",
				"MULTIPLE" => "N",
				"DEFAULT" => "/services/votes.php",
			),
		),
		"USER_PARAMETERS"=> Array(
		),
	);
?>
