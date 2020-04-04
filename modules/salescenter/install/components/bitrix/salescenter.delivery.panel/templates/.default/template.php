<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\UI\Extension,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
$APPLICATION->SetTitle(Loc::getMessage('SDP_SALESCENTER_TITLE'));

Extension::load([
	'ui.tilegrid',
	'ui.fonts.opensans',
	'sidepanel',
	'popup'
]);

?>
<div class="salescenter-delivery-title"><?=Loc::getMessage('SDP_SALESCENTER_DELIVERY_SUB_TITLE')?></div>
<div id="salescenter-delivery" class="salescenter-delivery"></div>

<script>
	BX.ready(function()
	{
		var deliveryParams = <?=CUtil::PhpToJSObject($arResult['deliveryPanelParams']);?>;
		deliveryParams.container = document.getElementById('salescenter-delivery');
		deliveryParams.sizeRatio = "55%";
		deliveryParams.itemMinWidth = 200;
		deliveryParams.tileMargin = 7;
		deliveryParams.itemType = 'BX.SaleCenterDelivery.TileGrid.Item';

		var gridTileServiceDelivery = new BX.TileGrid.Grid(deliveryParams);
		gridTileServiceDelivery.draw();
	});
</script>