<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

CJSCore::Init(["voximplant.common", "voximplant.callerid", "voximplant.numberrent", "ui.notification", "ui.alerts", "ui.buttons", "ui.buttons.icons", "sidepanel"]);
$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());

$button = new \Bitrix\UI\Buttons\Button([
	'text' => Loc::getMessage('VOX_LINES_CREATE_NUMBER_GROUP'),
	'icon' => \Bitrix\UI\Buttons\Icon::ADD,
	'color' => \Bitrix\UI\Buttons\Color::PRIMARY,
	'dataset' => [
		'toolbar-collapsed-icon' => Bitrix\UI\Buttons\Icon::ADD,
	]
]);
$button->addAttribute('id', 'add-group');
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($button);


$APPLICATION->IncludeComponent(
	"bitrix:main.ui.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"HEADERS" => $arResult["HEADERS"],
		"ROWS" => $arResult["ROWS"],
		"TOTAL_ROWS_COUNT" => $arResult["TOTAL_ROWS_COUNT"],
		"NAV_OBJECT" => $arResult["NAV_OBJECT"],
		"SORT" => $arResult["SORT"],
		"ENABLE_COLLAPSIBLE_ROWS" => true,
		"SHOW_ROW_CHECKBOXES" => false,
		"SHOW_SELECTED_COUNTER" => false,
		"SHOW_PAGINATION" => false,
		"FOOTER" => array(
			array("title" => GetMessage("VOX_QUEUE_LIST_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),

		"AJAX_MODE" => "Y",
		"AJAX_OPTION_JUMP"    => "N",
		"AJAX_OPTION_STYLE"   => "N",
		"AJAX_OPTION_HISTORY" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);

?>
<script>
	BX.message({
		"VOX_LINES_ADD_NUMBER_GROUP": '<?= GetMessageJS("VOX_LINES_ADD_NUMBER_GROUP")?>',
		"VOX_LINES_NUMBER_GROUP_NAME": '<?= GetMessageJS("VOX_LINES_NUMBER_GROUP_NAME")?>',
		"VOX_LINES_SELECT_UNASSIGNED_NUMBERS": '<?= GetMessageJS("VOX_LINES_SELECT_UNASSIGNED_NUMBERS")?>',
		"VOX_LINES_NO_UNASSIGNED_NUMBERS": '<?= GetMessageJS("VOX_LINES_NO_UNASSIGNED_NUMBERS")?>',
		"VOX_LINES_BUTTON_CREATE": '<?= GetMessageJS("VOX_LINES_BUTTON_CREATE")?>',
		"VOX_LINES_BUTTON_CANCEL": '<?= GetMessageJS("VOX_LINES_BUTTON_CANCEL")?>',
		"VOX_LINES_ERROR": '<?= GetMessageJS("VOX_LINES_ERROR")?>',
		"VOX_LINES_CONFIRM_ACTION": '<?= GetMessageJS("VOX_LINES_CONFIRM_ACTION")?>',
		"VOX_LINES_SIP_DELETE_CONFIRM": '<?= GetMessageJS("VOX_LINES_SIP_DELETE_CONFIRM")?>',
		"VOX_LINES_CALLERID_DELETE_CONFIRM": '<?= GetMessageJS("VOX_LINES_CALLERID_DELETE_CONFIRM")?>',
		"VOX_LINES_NUMBER_DELETE_CONFIRM": '<?= GetMessageJS("VOX_LINES_NUMBER_DELETE_CONFIRM")?>',
		"VOX_LINES_NUMBER_RENTED_IN_BUNDLE": '<?= GetMessageJS("VOX_LINES_NUMBER_RENTED_IN_BUNDLE")?>',
		"VOX_LINES_CONFIRM_BUNDLE_DISCONNECTION": '<?= GetMessageJS("VOX_LINES_CONFIRM_BUNDLE_DISCONNECTION")?>',
		"VOX_LINES_NUMBER_WILL_BE_DELETED": '<?= GetMessageJS("VOX_LINES_NUMBER_WILL_BE_DELETED")?>',
		"VOX_LINES_BUNDLE_WILL_BE_DELETED": '<?= GetMessageJS("VOX_LINES_BUNDLE_WILL_BE_DELETED")?>',
	});

	BX.ready(function()
	{
		BX.Voximplant.Lines.init({
			isTelephonyAvailable: '<?= $arResult['TELEPHONY_AVAILABLE'] ? 'Y' : 'N' ?>',
		});
	});
</script>
