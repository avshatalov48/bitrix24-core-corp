<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CJSCore::Init(["voximplant.common", "sidepanel"]);

$isBitrix24Template = (SITE_TEMPLATE_ID == "bitrix24");
if($isBitrix24Template)
{
	$this->SetViewTarget("pagetitle", 100);
}
?>
<div class="pagetitle-container pagetitle-align-right-container">
	<span id="add-ivr" class="webform-small-button webform-small-button-blue bx24-top-toolbar-add">
		<span class="webform-small-button-left"></span>
		<span class="webform-small-button-icon"></span>
		<span class="webform-small-button-text"><?=GetMessage('VOX_IVR_LIST_ADD_2')?></span>
		<span class="webform-small-button-right"></span>
	</span>
</div>
<?

if($isBitrix24Template)
{
	$this->EndViewTarget();
}

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
