<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

CJSCore::Init(['sidepanel', 'voximplant.common', 'ui.buttons', 'ui.buttons.icons']);

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$this->SetViewTarget("pagetitle", 100);
}
?>
<div class="pagetitle-container pagetitle-align-right-container">
	<button id="vox-blacklist-settings" class="ui-btn ui-btn-light-border ui-btn-themes ui-btn-icon-setting"></button>
	<button id="vox-blacklist-add" class="ui-btn ui-btn-md ui-btn-primary ui-btn-icon-add"><?= Loc::getMessage("VOX_BLACKLIST_ADD_TO_LIST") ?></button>
</div>
<?

if($isBitrix24Template)
{
	$this->EndViewTarget();
}

$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"HEADERS" => $arResult["HEADERS"],
		"ROWS" => $arResult["ROWS"],
		"NAV_OBJECT" => $arResult["NAV_OBJECT"],
		"SORT" => $arResult["SORT"],
		"FOOTER" => array(
			//array("title" => GetMessage("VOX_QUEUE_LIST_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),
		"AJAX_MODE" => "Y",
		"AJAX_ID" => CAjax::GetComponentID('bitrix:voximplant.blacklist', '.default', ''),
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>

<script>
	BX.message({
		'BLACKLIST_SETTINGS_TITLE': '<?= GetMessageJS('BLACKLIST_SETTINGS_TITLE')?>',
		'BLACKLIST_ENABLE': '<?= GetMessageJS('BLACKLIST_ENABLE')?>',
		'BLACKLIST_SAVE': '<?= GetMessageJS('BLACKLIST_SAVE')?>',
		'BLACKLIST_CANCEL': '<?= GetMessageJS('BLACKLIST_CANCEL')?>',
		'VOX_BLACKLIST_RINGS_COUNT': '<?= GetMessageJS('VOX_BLACKLIST_RINGS_COUNT')?>',
		'VOX_BLACKLIST_INTERVAL_IN_MINUTES': '<?= GetMessageJS('VOX_BLACKLIST_INTERVAL_IN_MINUTES')?>',
		'VOX_BLACKLIST_NUMBERS_TITLE': '<?= GetMessageJS('VOX_BLACKLIST_NUMBERS_TITLE')?>',
		'VOX_BLACKLIST_NUMBERS_SUBTITLE': '<?= GetMessageJS('VOX_BLACKLIST_NUMBERS_SUBTITLE')?>',
		'VOX_BLACKLIST_NUMBERS_HINT': '<?= GetMessageJS('VOX_BLACKLIST_NUMBERS_HINT')?>',
		'VOX_BLACKLIST_VALUE': '<?= GetMessageJS('VOX_BLACKLIST_VALUE')?>',
		'BLACKLIST_DELETE_CONFIRM': '<?= GetMessageJS('BLACKLIST_DELETE_CONFIRM')?>',
		'BLACKLIST_ERROR_TITLE': '<?= GetMessageJS('BLACKLIST_ERROR_TITLE')?>',
		'BLACKLIST_DELETE_ERROR': '<?= GetMessageJS('BLACKLIST_DELETE_ERROR')?>',
		'BLACKLIST_REGISTER_IN_CRM_2': '<?= GetMessageJS('BLACKLIST_REGISTER_IN_CRM_2')?>',
	});

	BX.ready(function()
	{
		BX("vox-blacklist-settings").addEventListener("click", function()
		{
			BX.Voximplant.Blacklist.showSettings();
		});

		BX("vox-blacklist-add").addEventListener("click", function()
		{
			BX.Voximplant.Blacklist.showNumberInput();
		});
	})
</script>

