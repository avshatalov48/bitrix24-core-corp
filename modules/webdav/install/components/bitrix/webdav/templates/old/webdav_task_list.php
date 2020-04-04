<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$APPLICATION->IncludeComponent("bitrix:bizproc.task.list", "", Array(
	"USER_ID" => "", 
	"WORKFLOW_ID" => "", 
	"TASK_EDIT_URL" => $arResult["URL_TEMPLATES"]["webdav_task"],
	"PAGE_ELEMENTS" => 0, 
	"PAGE_NAVIGATION_TEMPLATE" => "", 
	"SHOW_TRACKING" => "Y", 
	"SET_TITLE" => $arParams["SET_TITLE"],
	"SET_NAV_CHAIN" => $arParams["SET_TITLE"]),
	$component,
	array("HIDE_ICONS" => "Y")
);
?>