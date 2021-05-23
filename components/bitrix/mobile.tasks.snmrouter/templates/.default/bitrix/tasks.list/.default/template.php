<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/**
 * @var CMain $APPLICATION
 * @var array $arResult
 * @var array $arParams
 * @var CBitrixComponent $component
 */
$title = '';
if ($arResult["VIEW_STATE"]["SECTION_SELECTED"]["CODENAME"] == "VIEW_SECTION_ROLES")
{
	if (array_key_exists($arResult["VIEW_STATE"]["ROLE_SELECTED"]["CODENAME"], $arResult["VIEW_STATE"]["ROLES"]))
		$title = $arResult["VIEW_STATE"]["ROLES"][$arResult["VIEW_STATE"]["ROLE_SELECTED"]["CODENAME"]]["TITLE"];
}
else if ($arResult["VIEW_STATE"]["SPECIAL_PRESET_SELECTED"] && array_key_exists($arResult["VIEW_STATE"]["SPECIAL_PRESET_SELECTED"]["ID"], $arResult["VIEW_STATE"]["SPECIAL_PRESETS"]))
	$title = $arResult["VIEW_STATE"]["SPECIAL_PRESETS"][$arResult["VIEW_STATE"]["SPECIAL_PRESET_SELECTED"]["ID"]]["TITLE"];
else if ($arResult['VIEW_STATE']['SECTION_SELECTED']['CODENAME'] === 'VIEW_SECTION_ADVANCED_FILTER')
	$title = GetMessage("TASKS_ALL_TASKS");
else if ($arResult["VIEW_STATE"]["TASK_CATEGORY_SELECTED"] && array_key_exists($arResult["VIEW_STATE"]["TASK_CATEGORY_SELECTED"]["CODENAME"], $arResult["VIEW_STATE"]["TASK_CATEGORIES"]))
	$title = $arResult["VIEW_STATE"]["TASK_CATEGORIES"][$arResult["VIEW_STATE"]["TASK_CATEGORY_SELECTED"]["CODENAME"]];
\Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/log_mobile.js');
if (empty($arResult["ITEMS"]) && isset($_GET['F_SEARCH_ALT']) && $_GET['F_SEARCH_ALT'])
{
	?><div class="mobile-grid-stub-text"><?=GetMessage("TASKS_EMPTY_LIST2")?></div><?
}
else if (empty($arResult["ITEMS"]))
{
?>
<div class="mobile-grid mobile-grid-empty" >
	<div class="mobile-grid-stub">
		<div class="mobile-grid-stub-text"><?=GetMessage("TASKS_EMPTY_LIST")?></div>
		<a href="#" class="webform-button webform-button-blue" onclick="BX.Mobile.Tasks.createWindow(); return false;"><?=GetMessage("TASKS_EMPTY_LIST1")?></a>
	</div>
</div>
<?
}
else
{
$APPLICATION->SetPageProperty('BodyClass', 'task-list');
?><?=CJSCore::Init(array("tasks_util_query", "tasks_dayplan", "fx", "mobile_fastclick"), true);?><?
?><div id="bx-task-list"><?$APPLICATION->IncludeComponent("bitrix:mobile.interface.grid", "", array(
	"GRID_ID"=> $arParams["GRID_ID"],
	"FIELDS" => $arResult["FIELDS"],
	"ITEMS" => $arResult["ITEMS"],
	"SORT_EVENT_NAME" => "onTasksListSort",
	"FIELDS_EVENT_NAME" => "onTasksListFields",
	"RELOAD_GRID_AFTER_EVENT" => "N",
//	"FILTER_EVENT_NAME" => "onInvoiceListFilter",
	"NAV_PARAMS" => array(
		"PAGER_PARAM" => "PAGEN_".$arResult["NAV_PARAMS"]["PAGEN"],
		'PAGE_NAVNUM' => $arResult["NAV_PARAMS"]["PAGEN"],
		'PAGE_NAVCOUNT' => $arResult["FETCH_LIST_PARAMS"]["NAV_PARAMS"]["NavPageCount"],
		'PAGE_NUMBER' => $arResult["FETCH_LIST_PARAMS"]["NAV_PARAMS"]["iNumPage"]
	),
	"AJAX_PAGE_PATH" => $APPLICATION->GetCurPageParam("", array("PAGEN_".$arResult["NAV_PARAMS"]["PAGEN"])),
	"SHOW_SEARCH" => "Y"
));
?></div>
<?
}
?>
<script type="text/javascript">
BX.message({
	<?if ($title != '') :?>PAGE_TITLE : '<?=CUtil::JSEscape($title)?>',<?endif;?>
	TASKS_LIST_SORT : '<?=GetMessageJS("TASKS_LIST_SORT")?>',
	TASKS_LIST_FIELDS : '<?=GetMessageJS("TASKS_LIST_FIELDS")?>',
	TASKS_LIST_GROUP_ACTION_VIEW : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_VIEW")?>',
	TASKS_LIST_GROUP_ACTION_EDIT : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_EDIT")?>',
	TASKS_LIST_GROUP_ACTION_START : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_START")?>',
	TASKS_LIST_GROUP_ACTION_COMPLETE : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_COMPLETE")?>',
	TASKS_LIST_GROUP_ACTION_DEFER : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_DEFER")?>',
	TASKS_LIST_GROUP_ACTION_ADD_FAVORITE : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_ADD_FAVORITE")?>',
	TASKS_LIST_GROUP_ACTION_DELETE_FAVORITE : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_DELETE_FAVORITE")?>',
	TASKS_LIST_GROUP_ACTION_REMOVE : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_REMOVE")?>',
	TASKS_LIST_GROUP_ACTION_ERROR1 : '<?=GetMessageJS("TASKS_LIST_GROUP_ACTION_ERROR1")?>',
	TASKS_STATUS_METASTATE_EXPIRED : '<?=GetMessageJS("TASKS_STATUS_METASTATE_EXPIRED")?>',
	TASKS_STATUS_STATE_NEW : '<?=GetMessageJS("TASKS_STATUS_STATE_NEW")?>',
	TASKS_STATUS_STATE_PENDING : '<?=GetMessageJS("TASKS_STATUS_STATE_PENDING")?>',
	TASKS_STATUS_STATE_IN_PROGRESS : '<?=GetMessageJS("TASKS_STATUS_STATE_IN_PROGRESS")?>',
	TASKS_STATUS_STATE_SUPPOSEDLY_COMPLETED : '<?=GetMessageJS("TASKS_STATUS_STATE_SUPPOSEDLY_COMPLETED")?>',
	TASKS_STATUS_STATE_COMPLETED : '<?=GetMessageJS("TASKS_STATUS_STATE_COMPLETED")?>',
	TASKS_STATUS_STATE_DEFERRED : '<?=GetMessageJS("TASKS_STATUS_STATE_DEFERRED")?>',
	TASKS_STATUS_STATE_DECLINED : '<?=GetMessageJS("TASKS_STATUS_STATE_DECLINED")?>',
	TASKS_STATUS_STATE_UNKNOWN : '<?=GetMessageJS("TASKS_STATUS_STATE_UNKNOWN")?>',
	TASKS_TT_ERROR1_TITLE : '<?=GetMessageJS("TASKS_TT_ERROR1_TITLE")?>',
	TASKS_TT_ERROR1_DESC : '<?=GetMessageJS("TASKS_TT_ERROR1_DESC")?>',
	TASKS_TT_CONTINUE : '<?=GetMessageJS("TASKS_TT_CONTINUE")?>',
	TASKS_TT_CANCEL : '<?=GetMessageJS("TASKS_TT_CANCEL")?>'

});
BX.ready(function(){
	if (BX("bx-task-list"))
		FastClick.attach(BX("bx-task-list"));
	new BX.Mobile.Tasks.list({ tasksData : <?=CUtil::PhpToJSObject($arResult["ITEMSJS"])?>} );
});
</script><?