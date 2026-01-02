<?php

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

CJSCore::Init(['sidepanel', 'voximplant.common', 'ui.buttons', 'ui.buttons.icons']);

$buttonSettings = new \Bitrix\UI\Buttons\Button([
	'icon' => \Bitrix\UI\Buttons\Icon::SETTING,
	'color' => \Bitrix\UI\Buttons\Color::LIGHT_BORDER,
	'dataset' => [
		'toolbar-collapsed-icon' => Bitrix\UI\Buttons\Icon::SETTING,
	]
]);
$buttonSettings->addAttribute('id', 'vox-blacklist-settings');
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($buttonSettings);

$buttonAdd = new \Bitrix\UI\Buttons\Button([
	'text' => Loc::getMessage('VOX_BLACKLIST_ADD_TO_LIST'),
	'icon' => \Bitrix\UI\Buttons\Icon::ADD,
	'color' => \Bitrix\UI\Buttons\Color::PRIMARY,
	'dataset' => [
		'toolbar-collapsed-icon' => Bitrix\UI\Buttons\Icon::ADD,
	]
]);
$buttonAdd->addAttribute('id', 'vox-blacklist-add');
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($buttonAdd);

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

