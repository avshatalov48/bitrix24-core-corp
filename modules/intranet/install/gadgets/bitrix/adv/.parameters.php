<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:advertising.banner", $arCurrentValues);

$arParameters = Array(
		"PARAMETERS"=> Array(
			"TYPE"=>$arComponentProps["PARAMETERS"]["TYPE"],
		),
		"USER_PARAMETERS"=> Array(
		),
	);
?>
