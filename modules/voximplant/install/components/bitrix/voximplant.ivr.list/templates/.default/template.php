<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CJSCore::Init(["voximplant.common", "sidepanel"]);

$buttonAdd = new \Bitrix\UI\Buttons\Button([
	'text' => GetMessage(('VOX_IVR_LIST_ADD_2')),
	'icon' => Bitrix\UI\Buttons\Icon::ADD,
	'color' => \Bitrix\UI\Buttons\Color::PRIMARY,
	'dataset' => [
		'toolbar-collapsed-icon' => Bitrix\UI\Buttons\Icon::ADD,
	]
]);
$buttonAdd->addAttribute('id', 'add-ivr');
\Bitrix\UI\Toolbar\Facade\Toolbar::addButton($buttonAdd);

$APPLICATION->IncludeComponent("bitrix:ui.info.helper", "", array());

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
			array("title" => GetMessage("VOX_IVR_LIST_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),
		"AJAX_MODE" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);
?>

<script>
	BX.Voximplant.IvrList.setDefaults({
		createUrl: '<?= CUtil::JSEscape($arResult["CREATE_IVR_URL"])?>',
		isIvrEnabled: <?= $arResult["IS_IVR_ENABLED"] ? "true" : "false" ?>
	});

	BX.ready(function()
	{
		var instance = BX.Voximplant.IvrList.getInstance();
	})
</script>
