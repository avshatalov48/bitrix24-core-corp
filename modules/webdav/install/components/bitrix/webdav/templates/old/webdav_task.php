<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$APPLICATION->IncludeComponent(
	"bitrix:bizproc.task", 
	"", 
	Array(
	"TASK_ID" => $arResult["VARIABLES"]["ID"],
	"USER_ID" => 0, 
	"WORKFLOW_ID" => "", 
	"DOCUMENT_URL" => str_replace(
		array("#ELEMENT_ID#", "#ACTION#"), 
		array("#DOCUMENT_ID#", "EDIT"), $arResult["URL_TEMPLATES"]["element_edit"]), 
	"SET_TITLE" => $arParams["SET_TITLE"],
	"SET_NAV_CHAIN" => $arParams["SET_TITLE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>