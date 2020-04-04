<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arTemplates = array(
	"USER_INVITATION" => GetMessage("ITM_TEMPLATE_TYPE_USER_INVITATION"),
	"IM_NEW_NOTIFY" => GetMessage("ITM_TEMPLATE_TYPE_IM_NEW_NOTIFY"),
	"IM_NEW_MESSAGE" => GetMessage("ITM_TEMPLATE_TYPE_IM_NEW_MESSAGE"),
);

$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"TEMPLATE_TYPE" => Array(
			"NAME" => GetMessage("ITM_TEMPLATE_TYPE"),
			"TYPE" => "LIST",
			"MULTIPLE" => "N",
			"VALUES" => $arTemplates,
			"DEFAULT" => "USER_INVITATION",
			"ADDITIONAL_VALUES" => "N",
			"PARENT" => "VISUAL",
		),
	)
);
?>