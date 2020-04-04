<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arComponentProps = CComponentUtil::GetComponentProps("bitrix:support.ticket.list", $arCurrentValues);


$arLamp = Array(
	"" => GetMessage("GD_TICKETS_P_ALL"), 
	"red" => GetMessage("GD_TICKETS_P_RED"), 
	"green" => GetMessage("GD_TICKETS_P_GREEN"),
	"grey" => GetMessage("GD_TICKETS_P_GREY")
);

$arParameters = Array(
	"PARAMETERS" => Array(
		"PATH_TO_TICKET_EDIT"=> Array(
			"NAME" => GetMessage("GD_TICKETS_P_PATH_TO_TICKET_EDIT"),
			"TYPE" => "STRING",
			"DEFAULT" => "/extranet/services/support.php?ID=#ID#",
		),
		"PATH_TO_TICKET_NEW"=> Array(
			"NAME" => GetMessage("GD_TICKETS_P_PATH_TO_TICKET_NEW"),
			"TYPE" => "STRING",
			"DEFAULT" => "/extranet/services/support.php?show_wizard=Y",
		),
	),
	"USER_PARAMETERS" => Array(
		"ITEMS_COUNT" => $arComponentProps["PARAMETERS"]["TICKETS_PER_PAGE"],
		"LAMP" => Array(
			"NAME" => GetMessage("GD_TICKETS_P_LAMP"),
			"TYPE" => "LIST",
			"VALUES" => $arLamp,
			"MULTIPLE" => "Y",
			"DEFAULT" => ""
		),
	),
);

$arParameters["USER_PARAMETERS"]["ITEMS_COUNT"]["DEFAULT"] = "5";


?>
