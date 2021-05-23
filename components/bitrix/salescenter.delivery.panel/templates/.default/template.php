<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader,
	Bitrix\Main\UI\Extension,
	Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$messages = Loc::loadLanguageFile(__FILE__);

$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "") . "no-all-paddings no-hidden no-background");
$APPLICATION->SetTitle(Loc::getMessage('SDP_SALESCENTER_TITLE'));

if(Loader::includeModule('rest'))
{
	CJSCore::Init(["marketplace"]);
}

Extension::load([
	'ui.tilegrid',
	'ui.fonts.opensans',
	'sidepanel',
	'popup',
	'salescenter.manager',
	'applayout'
]);

?>
<div class="salescenter-delivery-title"><?=Loc::getMessage('SDP_SALESCENTER_DELIVERY_RECOMMENDATION_SUB_TITLE')?></div>
<div id="salescenter-delivery" class="salescenter-delivery"></div>

<script>
	BX.ready(function()
	{
		BX.message(<?=CUtil::PhpToJSObject($messages)?>);

		var deliveryParams = <?=CUtil::PhpToJSObject($arResult['deliveryPanelParams']);?>;
		deliveryParams.container = document.getElementById('salescenter-delivery');
		deliveryParams.sizeRatio = "55%";
		deliveryParams.itemMinWidth = 200;
		deliveryParams.tileMargin = 7;
		deliveryParams.itemType = 'BX.SaleCenterDelivery.TileGrid';

		BX.SaleCenterDelivery.init({
			deliveryParams: deliveryParams,
			signedParameters: '<?=CUtil::JSEscape($this->getComponent()->getSignedParameters())?>',
		});
	});
</script>