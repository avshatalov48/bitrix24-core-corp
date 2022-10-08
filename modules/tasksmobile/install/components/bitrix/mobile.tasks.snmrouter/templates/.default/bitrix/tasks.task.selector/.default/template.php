<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var CMain $APPLICATION
 * @var CDatabase $DB
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponent $component
 * @var CUser $USER
 */
$APPLICATION->SetPageProperty('BodyClass', 'task-list');
?><div id="bx-task-list"><?$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID" => "mobile_tasks_list_selector",
	"FIELDS" => $arResult["FIELDS"],
	"ITEMS" => $arResult["ITEMS"],
	"RELOAD_GRID_AFTER_EVENT" => "N",
	"AJAX_PAGE_PATH" => $APPLICATION->GetCurPageParam("", array("PAGEN_".$arResult["NAV_PARAMS"]["PAGEN"])),
	"SHOW_SEARCH" => "Y"
));

?>

