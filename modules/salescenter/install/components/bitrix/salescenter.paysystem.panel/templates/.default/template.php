<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
$APPLICATION->SetTitle(Loc::getMessage('SPP_SALESCENTER_TITLE'));
Extension::load([
	'ui.tilegrid',
	'ui.fonts.opensans',
	'sidepanel',
	'popup',
	'salescenter.manager',
]);

?>
<div class="salescenter-paysystem-title"><?=Loc::getMessage('SPP_SALESCENTER_PAYSYSTEM_SUB_TITLE')?></div>
<div id="salescenter-paysystem" class="salescenter-paysystem"></div>

<script>
	BX.ready(function()
	{
		var paySystemParams = <?=CUtil::PhpToJSObject($arResult['paySystemPanelParams']);?>;
		paySystemParams.container = document.getElementById('salescenter-paysystem');
		paySystemParams.sizeRatio = "55%";
		paySystemParams.itemMinWidth = 200;
		paySystemParams.tileMargin = 7;
		paySystemParams.itemType = 'BX.SaleCenterPaySystem.TileGrid.Item';

		var gridTileServiceDelivery = new BX.TileGrid.Grid(paySystemParams);
		gridTileServiceDelivery.draw();
	});
</script>